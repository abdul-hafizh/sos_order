<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangDetail;
use App\Models\BarangGambar;
use App\Models\Keranjang;
use App\Models\MasterProdukDetail;
use App\Models\MasterSatuan;
use App\Models\Category;
use App\Models\MasterProdukDetailGambar;
use App\Services\ImageEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BarangController extends Controller
{
    /**
     * Kode barang berurutan: ambil kode_barang terakhir dari t_barang,
     * ambil bagian angkanya, lalu tambahkan 1. Tanpa prefix huruf "R".
     */
    private function generateKodeBarang(): string
    {
        $lastKode = Barang::orderByDesc('id_barang')->value('kode_barang');

        preg_match('/(\d+)/', (string) $lastKode, $matches);
        $number = isset($matches[1]) ? ((int) $matches[1]) + 1 : 1000000;

        $kode = (string) $number;

        while (Barang::where('kode_barang', $kode)->exists()) {
            $number++;
            $kode = (string) $number;
        }

        return $kode;
    }

    private function uniqueKodeVariantRule(Request $request): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($request) {
            if (!$value) {
                return;
            }

            preg_match('/variants\.(\d+)\.kode_variant/', $attribute, $matches);
            $index = $matches[1] ?? null;
            $detailId = $index !== null ? $request->input("variants.$index.id_barang_detail") : null;

            $query = BarangDetail::where('kode_variant', $value);

            if ($detailId) {
                $query->where('id_barang_detail', '!=', $detailId);
            }

            if ($query->exists()) {
                $fail('Kode varian sudah digunakan.');
            }
        };
    }

    /**
     * Cocokkan satu keyword ke nama/kode barang, sekaligus ke data produk & detail
     * (nama produk, type, satuan, berat, ukuran, warna, karakter, uom) yang sudah di-join.
     */
    private function applyKeywordMatch($query, string $keyword): void
    {
        $like = "%{$keyword}%";

        $query->orWhere('t_barang.nama_barang', 'like', $like)
            ->orWhere('t_barang.kode_barang', 'like', $like)
            ->orWhereHas('produk', function ($qp) use ($like) {
                $qp->whereHas('produk', fn($q2) => $q2->where('nama_produk', 'like', $like))
                    ->orWhereHas('tipe', fn($q2) => $q2->where('nama', 'like', $like))
                    ->orWhereHas('satuan', fn($q2) => $q2->where('nama', 'like', $like))
                    ->orWhereHas('berat', fn($q2) => $q2->where('nama', 'like', $like))
                    ->orWhereHas('ukuran', fn($q2) => $q2->where('nama', 'like', $like))
                    ->orWhereHas('warna', fn($q2) => $q2->where('nama', 'like', $like))
                    ->orWhereHas('karakter', fn($q2) => $q2->where('nama', 'like', $like))
                    ->orWhereHas('uom', fn($q2) => $q2->where('nama_uom', 'like', $like));
            });
    }

    /**
     * Basis query MasterTipe, dipakai oleh dashboard() untuk grid tipe (level 1).
     */
    private function applyTipeKeywordMatch($query, string $keyword): void
    {
        $like = "%{$keyword}%";

        $query->orWhere('nama', 'like', $like)
            ->orWhereHas('details', function ($qd) use ($like) {
                $qd->whereHas('barang', fn($q2) => $q2->where('nama_barang', 'like', $like)->orWhere('kode_barang', 'like', $like))
                    ->orWhereHas('produk', fn($q2) => $q2->where('nama_produk', 'like', $like));
            });
    }

    /**
     * Basis query MasterProdukDetail, dipakai oleh dashboard() untuk grid varian
     * flat (level 2, setelah satu tipe dipilih).
     */
    private function applyVariantKeywordMatch($query, string $keyword): void
    {
        $like = "%{$keyword}%";

        $query->whereHas('barang', fn($q2) => $q2->where('nama_barang', 'like', $like)->orWhere('kode_barang', 'like', $like))
            ->orWhereHas('produk', fn($q2) => $q2->where('nama_produk', 'like', $like))
            ->orWhereHas('satuan', fn($q2) => $q2->where('nama', 'like', $like))
            ->orWhereHas('warna', fn($q2) => $q2->where('nama', 'like', $like))
            ->orWhereHas('ukuran', fn($q2) => $q2->where('nama', 'like', $like))
            ->orWhereHas('berat', fn($q2) => $q2->where('nama', 'like', $like))
            ->orWhereHas('karakter', fn($q2) => $q2->where('nama', 'like', $like))
            ->orWhereHas('uom', fn($q2) => $q2->where('nama_uom', 'like', $like));
    }

    public function index(Request $request)
    {
        $barangs = Barang::with(['details', 'category', 'produk.gambars'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id_barang')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Barang/Index', [
            'barangs' => $barangs,
            'filters' => $request->only(['search', 'per_page']),
            'list_satuan' => MasterSatuan::orderBy('nama')->get(),
        ]);
    }

    public function dashboard(Request $request)
    {
        $imageSimilarIds = collect(explode(',', (string) $request->input('image_similar_ids')))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $baseQuery = MasterProdukDetail::query()
            ->whereHas('barang')
            ->with(['barang', 'gambars', 'produk', 'satuan', 'berat', 'ukuran', 'warna', 'karakter', 'uom', 'tipe'])
            ->when($request->filled('category_code'), function ($query) use ($request) {
                $query->where('category_id', $request->category_code);
            })
            ->when($request->filled('id_tipe'), function ($query) use ($request) {
                $query->where('id_tipe', $request->id_tipe);
            });

        if (!empty($imageSimilarIds)) {
            $matches = (clone $baseQuery)->whereIn('id_produk_detail', $imageSimilarIds)->get();

            $sorted = $matches->sortBy(function ($item) use ($imageSimilarIds) {
                $pos = array_search($item->id_produk_detail, $imageSimilarIds, true);

                return $pos === false ? PHP_INT_MAX : $pos;
            })->values();

            $perPage = (int) ($request->per_page ?? 12);
            $page = (int) $request->input('page', 1);

            $variantList = new \Illuminate\Pagination\LengthAwarePaginator(
                $sorted->forPage($page, $perPage)->values(),
                $sorted->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } elseif ($request->boolean('image_no_results')) {
            // Pencarian gambar sudah dilakukan tapi tidak ada produk yang mirip
            // (di atas ambang similarity) - tampilkan list kosong, jangan fallback ke semua produk.
            $perPage = (int) ($request->per_page ?? 12);
            $page = (int) $request->input('page', 1);

            $variantList = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $variantList = $baseQuery
                ->when($request->filled('search'), function ($query) use ($request) {
                    $keywords = preg_split('/[\s,]+/', $request->search);

                    $query->where(function ($q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $keyword = trim($keyword);

                            if ($keyword === '') {
                                continue;
                            }

                            $this->applyVariantKeywordMatch($q, $keyword);
                        }
                    });
                })
                ->orderByDesc('created_at')
                ->paginate($request->per_page ?? 12)
                ->withQueryString();
        }

        // Ambil list kategori
        $categories = Category::select([
            'categorycode',
            'categoryname',
            'gambar',
        ])
            ->orderBy('categoryname')
            ->get();

        $keranjang = auth()->check() ? Keranjang::with([
            'details.gambar',
            'details.barang.produk.produk',
            'details.barang.produk.gambars',
            'details.barang.produk.tipe',
            'details.barang.produk.satuan',
            'details.barang.produk.berat',
            'details.barang.produk.ukuran',
            'details.barang.produk.warna',
            'details.barang.produk.karakter',
            'details.barang.produk.uom',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->first() : null;

        $cartItems = $keranjang?->details ?? collect();

        return Inertia::render('Dashboard', [
            'variantList' => $variantList,

            // List kategori
            'categories' => $categories,

            // Search + kategori + tipe yang sedang aktif
            'filters' => $request->only([
                'search',
                'category_code',
                'id_tipe',
            ]),

            'image_keyword' => $request->input('image_keyword'),
            'image_path' => $request->input('image_path'),
            'image_similar_ids' => $request->input('image_similar_ids'),

            'keranjang' => [
                'id_keranjang' => $keranjang?->id_keranjang,
                'items' => $cartItems,
                'total_item' => $cartItems->sum('qty'),
                'total_baris' => $cartItems->count(),
            ],
        ]);
    }
    
    public function searchByImage(Request $request, ImageEmbeddingService $embeddingService)
    {
        $request->validate([
            'image' => 'required|file|mimetypes:image/jpeg,image/png,image/webp|max:10240',
        ]);

        $file = $request->file('image');
        $imagePath = $file->store('permintaan-barang', 'public');

        session([
            'last_image_path' => $imagePath,
        ]);

        $result = $embeddingService->generateImageEmbedding($file->getRealPath(), $file->getMimeType());

        if (!$result) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Gagal menganalisis gambar, silakan coba lagi.');
        }

        $queryEmbedding = $result['embedding'];

        $scoresByDetail = [];

        MasterProdukDetailGambar::query()
            ->whereNotNull('embedding')
            ->select(['id_produk_detail', 'embedding'])
            ->chunk(200, function ($chunk) use (&$scoresByDetail, $queryEmbedding) {
                foreach ($chunk as $gambar) {
                    $vector = $gambar->embedding;

                    if (!is_array($vector) || empty($vector)) {
                        continue;
                    }

                    $score = ImageEmbeddingService::cosineSimilarity($queryEmbedding, $vector);
                    $detailId = $gambar->id_produk_detail;

                    if (!isset($scoresByDetail[$detailId]) || $score > $scoresByDetail[$detailId]) {
                        $scoresByDetail[$detailId] = $score;
                    }
                }
            });

        arsort($scoresByDetail);

        $threshold = (float) config('services.openai.image_similarity_threshold', 0.6);

        $rankedIds = array_slice(
            array_keys(array_filter($scoresByDetail, fn($score) => $score >= $threshold)),
            0,
            60
        );

        if (empty($rankedIds)) {
            return redirect()->route('dashboard', [
                'image_no_results' => 1,
                'image_keyword' => Str::limit($result['description'], 140),
                'image_path' => $imagePath,
            ])->with('error', 'Tidak ditemukan produk yang mirip dengan gambar ini.');
        }

        return redirect()->route('dashboard', [
            'image_similar_ids' => implode(',', $rankedIds),
            'image_keyword' => Str::limit($result['description'], 140),
            'image_path' => $imagePath,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'harga_beli' => 'nullable|numeric',
            'harga_beli_before' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'harga_jual_before' => 'nullable|numeric',
            'harga_jual_jumbo' => 'nullable|numeric',
            'harga_jual_jumbo_before' => 'nullable|numeric',
            'satuan' => 'nullable|string|max:50',
            'stok' => 'nullable|integer',
            'qty_pos' => 'nullable|integer',
            'min_stok' => 'nullable|integer',
            'max_stok' => 'nullable|integer',
            'category_code' => 'nullable|string|max:20|exists:m_category_2026,categorycode',

            'variants' => 'nullable|array',
            'variants.*.nama_variant' => 'nullable|string|max:100',
            'variants.*.kode_variant' => ['nullable', 'string', 'max:50', $this->uniqueKodeVariantRule($request)],
            'variants.*.harga_beli' => 'nullable|numeric',
            'variants.*.harga_jual' => 'nullable|numeric',
            'variants.*.harga_jual_jumbo' => 'nullable|numeric',
            'variants.*.stok' => 'nullable|integer',

            'variants.*.gambars' => 'nullable|array',
            'variants.*.gambars.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        DB::transaction(function () use ($request, $validated) {
            $hargaBeli = $validated['harga_beli'] ?? 0;
            $hargaJual = $validated['harga_jual'] ?? 0;
            $hargaJualJumbo = $validated['harga_jual_jumbo'] ?? 0;

            $barang = Barang::create([
                'kode_barang' => $this->generateKodeBarang(),
                'nama_barang' => $validated['nama_barang'],
                'harga_beli' => $hargaBeli,
                'harga_beli_before' => $validated['harga_beli_before'] ?? 0,
                'harga_jual' => $hargaJual,
                'harga_jual_before' => $validated['harga_jual_before'] ?? 0,
                'harga_jual_jumbo' => $hargaJualJumbo,
                'harga_jual_jumbo_before' => $validated['harga_jual_jumbo_before'] ?? 0,
                'margin' => $hargaJual - $hargaBeli,
                'satuan' => $validated['satuan'] ?? null,
                'stok' => $validated['stok'] ?? 0,
                'qty_pos' => $validated['qty_pos'] ?? 0,
                'min_stok' => $validated['min_stok'] ?? 0,
                'max_stok' => $validated['max_stok'] ?? 0,
                'category_code' => $validated['category_code'] ?? null,
                'min_vendor' => 0,
                'min_cabang' => 0,
                'kirim_langsung' => 0,
                'active' => 1,
                'modified_by' => auth()->id(),
                'modified_date' => now(),
            ]);

            $variants = $request->variants ?? [];

            if (count($variants) === 0) {
                $variants[] = [
                    'nama_variant' => 'Default',
                    'kode_variant' => null,
                    'harga_beli' => $validated['harga_beli'] ?? 0,
                    'harga_jual' => $validated['harga_jual'] ?? 0,
                    'harga_jual_jumbo' => null,
                    'stok' => $validated['stok'] ?? 0,
                ];
            }

            foreach ($variants as $index => $variant) {
                $detail = BarangDetail::create([
                    'id_barang' => $barang->id_barang,
                    'nama_variant' => $variant['nama_variant'] ?: 'Default',
                    'kode_variant' => $variant['kode_variant'] ?? null,
                    'harga_beli' => $variant['harga_beli'] ?? $barang->harga_beli,
                    'harga_jual' => $variant['harga_jual'] ?? $barang->harga_jual,
                    'harga_jual_jumbo' => $variant['harga_jual_jumbo'] ?? null,
                    'stok' => $variant['stok'] ?? 0,
                    'active' => 1,
                    'modified_by' => auth()->id(),
                    'modified_date' => now(),
                ]);

                if ($request->hasFile("variants.$index.gambars")) {
                    foreach ($request->file("variants.$index.gambars") as $file) {
                        $path = $file->store('barang', 'public');

                        BarangGambar::create([
                            'id_barang_detail' => $detail->id_barang_detail,
                            'nama_file' => $file->getClientOriginalName(),
                            'path_file' => $path,
                            'active' => 1,
                            'modified_by' => auth()->id(),
                            'modified_date' => now(),
                        ]);
                    }
                }
            }
        });

        return back()->with('message', 'Barang berhasil ditambahkan');
    }

    public function storeBarangBaru(Request $request)
    {
        $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'qty' => ['required', 'numeric', 'min:1'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string'],
            'gambar' => ['nullable', 'image', 'max:10240'],
            'image_path' => ['nullable', 'string'],
        ]);

        $gambarPath = $request->image_path ?: session('last_image_path');

        if (!$gambarPath && $request->hasFile('gambar')) {
            $gambarPath = $request->file('gambar')->store('permintaan-barang', 'public');
        }

        $keranjang = Keranjang::firstOrCreate([
            'user_id' => auth()->id(),
            'status' => 'draft',
        ]);

        $keranjang->items()->create([
            'id_barang' => null,
            'nama_barang' => $request->nama_barang,
            'qty' => $request->qty,
            'satuan' => $request->satuan,
            'catatan' => $request->catatan,
            'gambar_permintaan' => $gambarPath,
            'tipe_item' => 'barang_baru',
        ]);

        return back()->with('success', 'Permintaan barang baru berhasil dimasukkan ke keranjang.');
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::with(['details.gambars'])->findOrFail($id);

        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'harga_beli' => 'nullable|numeric',
            'harga_beli_before' => 'nullable|numeric',
            'harga_jual' => 'nullable|numeric',
            'harga_jual_before' => 'nullable|numeric',
            'harga_jual_jumbo' => 'nullable|numeric',
            'harga_jual_jumbo_before' => 'nullable|numeric',
            'satuan' => 'nullable|string|max:50',
            'stok' => 'nullable|integer',
            'qty_pos' => 'nullable|integer',
            'min_stok' => 'nullable|integer',
            'max_stok' => 'nullable|integer',
            'category_code' => 'nullable|string|max:20|exists:m_category_2026,categorycode',

            'variants' => 'nullable|array',
            'variants.*.id_barang_detail' => 'nullable|integer',
            'variants.*.nama_variant' => 'nullable|string|max:100',
            'variants.*.kode_variant' => ['nullable', 'string', 'max:50', $this->uniqueKodeVariantRule($request)],
            'variants.*.harga_beli' => 'nullable|numeric',
            'variants.*.harga_jual' => 'nullable|numeric',
            'variants.*.harga_jual_jumbo' => 'nullable|numeric',
            'variants.*.stok' => 'nullable|integer',

            'variants.*.gambars' => 'nullable|array',
            'variants.*.gambars.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'variants.*.deleted_gambar_ids' => 'nullable|array',
            'variants.*.deleted_gambar_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $validated, $barang) {
            $hargaBeli = $validated['harga_beli'] ?? 0;
            $hargaJual = $validated['harga_jual'] ?? 0;
            $hargaJualJumbo = $validated['harga_jual_jumbo'] ?? 0;

            $barang->update([
                'nama_barang' => $validated['nama_barang'],
                'harga_beli' => $hargaBeli,
                'harga_beli_before' => $validated['harga_beli_before'] ?? 0,
                'harga_jual' => $hargaJual,
                'harga_jual_before' => $validated['harga_jual_before'] ?? 0,
                'harga_jual_jumbo' => $hargaJualJumbo,
                'harga_jual_jumbo_before' => $validated['harga_jual_jumbo_before'] ?? 0,
                'margin' => $hargaJual - $hargaBeli,
                'satuan' => $validated['satuan'] ?? null,
                'stok' => $validated['stok'] ?? 0,
                'qty_pos' => $validated['qty_pos'] ?? 0,
                'min_stok' => $validated['min_stok'] ?? 0,
                'max_stok' => $validated['max_stok'] ?? 0,
                'category_code' => $validated['category_code'] ?? null,
                'min_vendor' => 0,
                'min_cabang' => 0,
                'kirim_langsung' => 0,
                'modified_by' => auth()->id(),
                'modified_date' => now(),
            ]);

            $variants = $request->input('variants', []);

            if (count($variants) === 0) {
                $variants[] = [
                    'id_barang_detail' => null,
                    'nama_variant' => 'Default',
                    'kode_variant' => null,
                    'harga_beli' => $validated['harga_beli'] ?? 0,
                    'harga_jual' => $validated['harga_jual'] ?? 0,
                    'harga_jual_jumbo' => null,
                    'stok' => $validated['stok'] ?? 0,
                    'deleted_gambar_ids' => [],
                ];
            }

            $submittedDetailIds = [];

            foreach ($variants as $index => $variant) {
                $detailId = $variant['id_barang_detail'] ?? null;

                $detail = null;

                if ($detailId) {
                    $detail = BarangDetail::where('id_barang', $barang->id_barang)
                        ->where('id_barang_detail', $detailId)
                        ->first();
                }

                if (!$detail) {
                    $detail = BarangDetail::create([
                        'id_barang' => $barang->id_barang,
                        'nama_variant' => !empty($variant['nama_variant']) ? $variant['nama_variant'] : 'Default',
                        'kode_variant' => $variant['kode_variant'] ?? null,
                        'harga_beli' => $variant['harga_beli'] ?? $barang->harga_beli,
                        'harga_jual' => $variant['harga_jual'] ?? $barang->harga_jual,
                        'harga_jual_jumbo' => $variant['harga_jual_jumbo'] ?? null,
                        'stok' => $variant['stok'] ?? 0,
                        'active' => 1,
                        'modified_by' => auth()->id(),
                        'modified_date' => now(),
                    ]);
                } else {
                    $detail->update([
                        'nama_variant' => !empty($variant['nama_variant']) ? $variant['nama_variant'] : 'Default',
                        'kode_variant' => $variant['kode_variant'] ?? null,
                        'harga_beli' => $variant['harga_beli'] ?? $barang->harga_beli,
                        'harga_jual' => $variant['harga_jual'] ?? $barang->harga_jual,
                        'harga_jual_jumbo' => $variant['harga_jual_jumbo'] ?? null,
                        'stok' => $variant['stok'] ?? 0,
                        'active' => 1,
                        'modified_by' => auth()->id(),
                        'modified_date' => now(),
                    ]);
                }

                $submittedDetailIds[] = $detail->id_barang_detail;

                /*
                |--------------------------------------------------------------------------
                | Hapus gambar lama yang diklik silang
                |--------------------------------------------------------------------------
                */
                $deletedGambarIds = $variant['deleted_gambar_ids'] ?? [];

                if (!empty($deletedGambarIds)) {
                    $deletedGambars = BarangGambar::where('id_barang_detail', $detail->id_barang_detail)
                        ->whereIn('id_barang_gambar', $deletedGambarIds)
                        ->get();

                    foreach ($deletedGambars as $gambar) {
                        if (!empty($gambar->path_file)) {
                            Storage::disk('public')->delete($gambar->path_file);
                        }

                        $gambar->delete();
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Upload gambar baru
                |--------------------------------------------------------------------------
                */
                if ($request->hasFile("variants.$index.gambars")) {
                    foreach ($request->file("variants.$index.gambars") as $file) {
                        $path = $file->store('barang', 'public');

                        BarangGambar::create([
                            'id_barang_detail' => $detail->id_barang_detail,
                            'nama_file' => $file->getClientOriginalName(),
                            'path_file' => $path,
                            'active' => 1,
                            'modified_by' => auth()->id(),
                            'modified_date' => now(),
                        ]);
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Hapus varian yang sudah tidak dikirim dari frontend
            |--------------------------------------------------------------------------
            */
            $deletedDetails = BarangDetail::with('gambars')
                ->where('id_barang', $barang->id_barang)
                ->whereNotIn('id_barang_detail', $submittedDetailIds)
                ->get();

            foreach ($deletedDetails as $detail) {
                foreach ($detail->gambars as $gambar) {
                    if (!empty($gambar->path_file)) {
                        Storage::disk('public')->delete($gambar->path_file);
                    }

                    $gambar->delete();
                }

                $detail->delete();
            }
        });

        return back()->with('message', 'Barang berhasil diupdate');
    }

    public function destroy($id)
    {
        $barang = Barang::with(['details.gambars'])->findOrFail($id);

        DB::transaction(function () use ($barang) {
            foreach ($barang->details as $detail) {
                foreach ($detail->gambars as $gambar) {
                    Storage::disk('public')->delete($gambar->path_file);
                    $gambar->delete();
                }

                $detail->delete();
            }

            $barang->delete();
        });

        return back()->with('message', 'Barang berhasil dihapus');
    }
}