<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangDetail;
use App\Models\BarangGambar;
use App\Models\Keranjang;
use App\Models\MasterSatuan;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BarangController extends Controller
{
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
        $barangs = Barang::with(['details', 'produk.gambars'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $keywords = preg_split('/[\s,]+/', $request->search);

                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);

                        if ($keyword === '') {
                            continue;
                        }

                        $q->orWhere('nama_barang', 'like', "%{$keyword}%")
                            ->orWhere('kode_barang', 'like', "%{$keyword}%");
                    }
                });
            })

            // Filter berdasarkan kategori
            ->when($request->filled('category_code'), function ($query) use ($request) {
                $query->where(
                    'category_code',
                    $request->category_code
                );
            })

            ->orderByDesc('id_barang')
            ->paginate(12)
            ->withQueryString();

        // Ambil list kategori
        $categories = Category::select([
            'categorycode',
            'categoryname',
        ])
            ->orderBy('categoryname')
            ->get();

        $keranjang = Keranjang::with([
            'details.gambar',
            'details.barang.produk.gambars',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'draft')
            ->first();

        $cartItems = $keranjang?->details ?? collect();

        return Inertia::render('Dashboard', [
            'barangs' => $barangs,

            // List kategori
            'categories' => $categories,

            // Search + kategori yang sedang aktif
            'filters' => $request->only([
                'search',
                'category_code',
            ]),

            'image_keyword' => $request->input('image_keyword'),
            'image_path' => $request->input('image_path'),

            'keranjang' => [
                'id_keranjang' => $keranjang?->id_keranjang,
                'items' => $cartItems,
                'total_item' => $cartItems->sum('qty'),
                'total_baris' => $cartItems->count(),
            ],
        ]);
    }
    public function searchByImage(Request $request)
    {
        $file = $request->file('image');

        $request->validate([
            'image' => 'required|file|mimetypes:image/jpeg,image/png,image/webp|max:10240',
        ]);

        $imagePath = $file->store('permintaan-barang', 'public');

        session([
            'last_image_path' => $imagePath,
        ]);

        $base64 = base64_encode(file_get_contents($file->getRealPath()));
        $mime = $file->getMimeType();

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->timeout(60)
            ->post('https://api.openai.com/v1/responses', [
                'model' => env('OPENAI_MODEL', 'gpt-5.4-mini'),
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => 'Analisis gambar produk ini. Berikan 3 sampai 8 keyword pencarian barang dalam bahasa Indonesia dalam 1 kata saja. Jawab hanya JSON: {"keywords":["..."]}',
                            ],
                            [
                                'type' => 'input_image',
                                'image_url' => "data:{$mime};base64,{$base64}",
                            ],
                        ],
                    ],
                ],
            ]);

        $jsonText = $response->json('output.0.content.0.text') ?? '{}';

        $result = json_decode($jsonText, true);

        $keywords = $result['keywords'] ?? [];

        $keywordText = collect($keywords)
            ->filter()
            ->map(fn($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->implode(' ');

        $imageKeywordText = collect($keywords)
            ->filter()
            ->map(fn($item) => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        if (!$keywordText) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Keyword dari gambar tidak berhasil dibaca.');
        }

        return redirect()->route('dashboard', [
            'search' => $keywordText,
            'image_keyword' => $imageKeywordText,
            'image_path' => $imagePath,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|unique:t_barang,kode_barang',
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
            'category_code' => 'nullable|string|max:20|exists:m_category,categorycode',

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
                'kode_barang' => $validated['kode_barang'],
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
            'kode_barang' => 'required|unique:t_barang,kode_barang,' . $id . ',id_barang',
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
            'category_code' => 'nullable|string|max:20|exists:m_category,categorycode',

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
                'kode_barang' => $validated['kode_barang'],
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