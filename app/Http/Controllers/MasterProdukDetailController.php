<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Category;
use App\Models\MasterBerat;
use App\Models\MasterKarakter;
use App\Models\MasterProduk;
use App\Models\MasterProdukDetail;
use App\Models\MasterProdukDetailGambar;
use App\Models\MasterSatuan;
use App\Models\MasterTipe;
use App\Models\MasterUkuran;
use App\Models\MasterUom;
use App\Models\MasterWarna;
use App\Services\ImageEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MasterProdukDetailController extends Controller
{
    public function index(Request $request)
    {
        $produkDetail = MasterProdukDetail::with(['produk', 'category', 'tipe', 'satuan', 'berat', 'ukuran', 'warna', 'karakter', 'uom', 'gambars', 'barang'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('kode_barang', 'like', "%{$search}%")
                        ->orWhereHas('produk', function ($q2) use ($search) {
                            $q2->where('nama_produk', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('id_produk_detail')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Produk-Detail/Index', [
            'produkDetail' => $produkDetail,
            'filters' => $request->only(['search', 'per_page']),
            'list_produk' => MasterProduk::orderBy('nama_produk')->get(['id_produk', 'nama_produk', 'deskripsi']),
            'list_kategori' => Category::orderBy('categoryname')->get(['categorycode', 'categoryname']),
            'list_tipe' => MasterTipe::orderBy('nama')->get(),
            'list_satuan' => MasterSatuan::orderBy('nama')->get(),
            'list_berat' => MasterBerat::orderBy('nama')->get(),
            'list_ukuran' => MasterUkuran::orderBy('nama')->get(),
            'list_warna' => MasterWarna::orderBy('nama')->get(),
            'list_karakter' => MasterKarakter::orderBy('nama')->get(),
            'list_uom' => MasterUom::orderBy('nama_uom')->get(),
        ]);
    }

    public function barangOptions(Request $request)
    {
        $usedKodeBarang = MasterProdukDetail::whereNotNull('kode_barang')
            ->when($request->current, fn($q, $current) => $q->where('kode_barang', '!=', $current))
            ->pluck('kode_barang');

        $barangs = Barang::query()
            ->whereNotIn('kode_barang', $usedKodeBarang)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            })
            ->orderBy('nama_barang')
            ->limit(20)
            ->get(['id_barang', 'kode_barang', 'nama_barang']);

        return response()->json($barangs);
    }

    /**
     * Bangun payload MasterProdukDetailGambar dari file upload, sekaligus
     * generate embedding visualnya (vision -> deskripsi -> text-embedding).
     * Kegagalan OpenAI tidak menggagalkan simpan gambar, hanya embedding-nya kosong.
     */
    private function buildGambarData(int $idProdukDetail, $file, ImageEmbeddingService $embeddingService): array
    {
        $path = $file->store('produk', 'public');

        $gambarData = [
            'id_produk_detail' => $idProdukDetail,
            'nama_file' => Crypt::encryptString($file->getClientOriginalName()),
            'path_file' => $path,
        ];

        try {
            $result = $embeddingService->generateImageEmbedding($file->getRealPath(), $file->getMimeType());

            if ($result) {
                $gambarData['description'] = $result['description'];
                $gambarData['embedding'] = $result['embedding'];
                $gambarData['embedding_model'] = $result['model'];
                $gambarData['embedding_generated_at'] = now();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal membuat embedding gambar produk', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $gambarData;
    }

    /**
     * Generate embedding hanya untuk gambar produk detail yang belum punya embedding
     * (kolomnya masih null, mis. karena gagal saat pertama kali disimpan). Gambar yang
     * embedding-nya sudah terisi dilewati saja, tidak di-generate ulang.
     */
    private function backfillMissingEmbeddings(MasterProdukDetail $produkDetail, ImageEmbeddingService $embeddingService): void
    {
        $gambars = MasterProdukDetailGambar::where('id_produk_detail', $produkDetail->id_produk_detail)
            ->whereNull('embedding')
            ->get();

        foreach ($gambars as $gambar) {
            $absolutePath = Storage::disk('public')->path($gambar->path_file);

            if (!is_file($absolutePath)) {
                continue;
            }

            try {
                $mime = File::mimeType($absolutePath) ?: 'image/jpeg';
                $result = $embeddingService->generateImageEmbedding($absolutePath, $mime);

                if ($result) {
                    $gambar->update([
                        'description' => $result['description'],
                        'embedding' => $result['embedding'],
                        'embedding_model' => $result['model'],
                        'embedding_generated_at' => now(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('Gagal membuat embedding gambar produk (backfill saat update)', [
                    'id_produk_gambar' => $gambar->id_produk_gambar,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function store(Request $request, ImageEmbeddingService $embeddingService)
    {
        $validated = $request->validate([
            'id_produk' => 'required|integer|exists:master_produk,id_produk',
            'category_id' => 'nullable|string|max:20|exists:m_category_2026,categorycode',
            'kode_barang' => 'nullable|string|max:50|unique:master_produk_detail,kode_barang',
            'id_tipe' => 'nullable|integer|exists:master_tipe,id_tipe',
            'id_satuan' => 'nullable|integer|exists:master_satuan,id_satuan',
            'id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',
            'id_uom' => 'nullable|integer|exists:master_uom,id_uom',

            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated, $embeddingService) {
            $produkDetail = MasterProdukDetail::create($validated);

            if ($request->hasFile('foto')) {
                foreach ($request->file('foto') as $file) {
                    MasterProdukDetailGambar::create(
                        $this->buildGambarData($produkDetail->id_produk_detail, $file, $embeddingService)
                    );
                }
            }
        });

        return back()->with('success', 'Produk detail berhasil ditambahkan');
    }

    public function update(Request $request, $id, ImageEmbeddingService $embeddingService)
    {
        $produkDetail = MasterProdukDetail::findOrFail($id);

        $validated = $request->validate([
            'id_produk' => 'required|integer|exists:master_produk,id_produk',
            'category_id' => 'nullable|string|max:20|exists:m_category_2026,categorycode',
            'kode_barang' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('master_produk_detail', 'kode_barang')->ignore($produkDetail->id_produk_detail, 'id_produk_detail'),
            ],
            'id_tipe' => 'nullable|integer|exists:master_tipe,id_tipe',
            'id_satuan' => 'nullable|integer|exists:master_satuan,id_satuan',
            'id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',
            'id_uom' => 'nullable|integer|exists:master_uom,id_uom',

            'foto' => 'nullable|array',
            'foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'deleted_gambar_ids' => 'nullable|array',
            'deleted_gambar_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $validated, $produkDetail, $embeddingService) {
            $produkDetail->update(collect($validated)->except(['foto', 'deleted_gambar_ids'])->all());

            $deletedGambarIds = $validated['deleted_gambar_ids'] ?? [];

            if (!empty($deletedGambarIds)) {
                $deletedGambars = MasterProdukDetailGambar::where('id_produk_detail', $produkDetail->id_produk_detail)
                    ->whereIn('id_produk_gambar', $deletedGambarIds)
                    ->get();

                foreach ($deletedGambars as $gambar) {
                    if (!empty($gambar->path_file)) {
                        Storage::disk('public')->delete($gambar->path_file);
                    }

                    $gambar->delete();
                }
            }

            if ($request->hasFile('foto')) {
                foreach ($request->file('foto') as $file) {
                    MasterProdukDetailGambar::create(
                        $this->buildGambarData($produkDetail->id_produk_detail, $file, $embeddingService)
                    );
                }
            }

            $this->backfillMissingEmbeddings($produkDetail, $embeddingService);
        });

        return back()->with('success', 'Produk detail berhasil diupdate');
    }

}
