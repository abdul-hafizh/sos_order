<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangDetail;
use App\Models\BarangGambar;
use App\Models\Keranjang;
use App\Models\MasterBerat;
use App\Models\MasterKarakter;
use App\Models\MItem;
use App\Models\MasterProduk;
use App\Models\MasterProdukDetail;
use App\Models\MasterSatuan;
use App\Models\MasterTipe;
use App\Models\MasterUkuran;
use App\Models\MasterUom;
use App\Models\MasterWarna;
use App\Models\Category;
use App\Models\MasterProdukDetailGambar;
use App\Models\Ppn;
use App\Models\Spk;
use App\Services\ImageEmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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
            'list_produk' => MasterProduk::orderBy('nama_produk')->get(['id_produk', 'nama_produk', 'deskripsi']),
            'list_kategori' => Category::get(['categorycode', 'categoryname']),
            'list_tipe' => MasterTipe::orderBy('nama')->get(),
            'list_berat' => MasterBerat::orderBy('nama')->get(),
            'list_ukuran' => MasterUkuran::orderBy('nama')->get(),
            'list_warna' => MasterWarna::orderBy('nama')->get(),
            'list_karakter' => MasterKarakter::orderBy('nama')->get(),
            'list_uom' => MasterUom::orderBy('nama_uom')->get(),
        ]);
    }

    /**
     * Ringkasan produk dikelompokkan per MasterTipe (dipakai dashboard() saat mode browse):
     * untuk tiap tipe, ambil varian dengan harga_jual paling rendah sebagai representasi
     * (gambar, kategori, dan harga "mulai dari" ikut dari varian termurah itu).
     */
    private function buildTipeSummaries(Request $request)
    {
        $details = MasterProdukDetail::query()
            ->whereHas('barang')
            ->whereNotNull('id_tipe')
            ->with([
                'barang:id_barang,kode_barang,harga_jual',
                'gambars:id_produk_gambar,id_produk_detail,path_file',
                'category:categorycode,categoryname',
                'tipe:id_tipe,nama',
            ])
            ->when($request->filled('category_code'), function ($query) use ($request) {
                $query->where('category_id', $request->category_code);
            })
            ->get();

        return $details
            ->groupBy('id_tipe')
            ->map(function ($items) {
                $cheapest = $items->sortBy(fn($d) => $d->barang?->harga_jual ?? PHP_INT_MAX)->first();
                $gambar = $cheapest->gambars->first();

                return [
                    'id_tipe' => $cheapest->id_tipe,
                    'nama' => $cheapest->tipe?->nama ?? 'Lainnya',
                    'harga_terendah' => (float) ($cheapest->barang?->harga_jual ?? 0),
                    'category_name' => $cheapest->category?->categoryname,
                    'gambar_url' => $gambar ? asset('storage/' . $gambar->path_file) : null,
                    'jumlah_varian' => $items->count(),
                ];
            })
            ->sortBy('nama')
            ->values();
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

        // Mode browse (belum ada pencarian teks/gambar & belum memilih tipe tertentu):
        // tampilkan dikelompokkan per MasterTipe. Begitu user search atau memilih tipe,
        // kembali ke tampilan varian flat seperti biasa.
        $isBrowseMode = !$request->filled('search')
            && empty($imageSimilarIds)
            && !$request->boolean('image_no_results')
            && !$request->filled('id_tipe');

        $tipeList = null;
        $variantList = null;

        if ($isBrowseMode) {
            $tipeList = $this->buildTipeSummaries($request);
        } elseif (!empty($imageSimilarIds)) {
            $matches = (clone $baseQuery)->whereIn('id_produk_detail', $imageSimilarIds)->get();

            // Petakan id_produk_detail => id_produk_gambar yang benar-benar menghasilkan
            // skor kemiripan tertinggi (dikirim searchByImage(), sejajar urutan dgn image_similar_ids).
            $matchedGambarIds = collect(explode(',', (string) $request->input('image_matched_gambar_ids')))
                ->map(fn($id) => (int) trim($id))
                ->values()
                ->pad(count($imageSimilarIds), null)
                ->take(count($imageSimilarIds));

            $matchedGambarMap = collect($imageSimilarIds)
                ->combine($matchedGambarIds)
                ->filter();

            $matches->each(function ($item) use ($matchedGambarMap) {
                $matchedId = $matchedGambarMap[$item->id_produk_detail] ?? null;

                if ($matchedId && $item->relationLoaded('gambars')) {
                    $item->setRelation(
                        'gambars',
                        $item->gambars->sortByDesc(fn($g) => $g->id_produk_gambar === $matchedId)->values()
                    );
                }
            });

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
        ])->get();

        // Ambil list service (MasterTipe) beserta sub-kategori masing-masing
        $services = \App\Models\MasterTipe::query()
            ->select(['id_tipe', 'nama'])
            ->get()
            ->map(function ($tipe) {
                $categoryCodes = \App\Models\MasterProdukDetail::where('id_tipe', $tipe->id_tipe)
                    ->whereNotNull('category_id')
                    ->distinct()
                    ->pluck('category_id');

                $subCategories = \App\Models\Category::whereIn('categorycode', $categoryCodes)
                    ->select(['categorycode', 'categoryname'])
                    ->get()
                    ->map(fn($c) => [
                        'code' => $c->categorycode,
                        'name' => $c->categoryname,
                    ])
                    ->values();

                return [
                    'id_tipe' => $tipe->id_tipe,
                    'nama' => $tipe->nama,
                    'categories' => $subCategories,
                ];
            })
            ->values();

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
            'tipeList' => $tipeList,

            // List kategori & services
            'categories' => $categories,
            'services' => $services,

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
        $bestGambarByDetail = [];

        MasterProdukDetailGambar::query()
            ->whereNotNull('embedding')
            ->select(['id_produk_gambar', 'id_produk_detail', 'embedding'])
            ->chunk(200, function ($chunk) use (&$scoresByDetail, &$bestGambarByDetail, $queryEmbedding) {
                foreach ($chunk as $gambar) {
                    $vector = $gambar->embedding;

                    if (!is_array($vector) || empty($vector)) {
                        continue;
                    }

                    $score = ImageEmbeddingService::cosineSimilarity($queryEmbedding, $vector);
                    $detailId = $gambar->id_produk_detail;

                    if (!isset($scoresByDetail[$detailId]) || $score > $scoresByDetail[$detailId]) {
                        $scoresByDetail[$detailId] = $score;
                        // Simpan gambar mana yang benar-benar menghasilkan skor tertinggi,
                        // supaya nanti bisa ditampilkan sebagai foto utama di hasil pencarian
                        // (produk bisa punya banyak foto, belum tentu foto pertamanya yang cocok).
                        $bestGambarByDetail[$detailId] = $gambar->id_produk_gambar;
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

        $matchedGambarIds = array_map(fn($id) => $bestGambarByDetail[$id] ?? '', $rankedIds);

        return redirect()->route('dashboard', [
            'image_similar_ids' => implode(',', $rankedIds),
            'image_matched_gambar_ids' => implode(',', $matchedGambarIds),
            'image_keyword' => Str::limit($result['description'], 140),
            'image_path' => $imagePath,
        ]);
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

    /**
     * Bangun payload MasterProdukDetailGambar dari file upload, sekaligus
     * generate embedding visualnya (vision -> deskripsi -> text-embedding).
     * Kegagalan OpenAI tidak menggagalkan simpan gambar, hanya embedding-nya kosong.
     */
    private function buildProdukDetailGambarData(int $idProdukDetail, $file, ImageEmbeddingService $embeddingService): array
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
            Log::error('Gagal membuat embedding gambar produk (dari menu Barang)', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }

        return $gambarData;
    }

    /**
     * Generate embedding hanya untuk gambar produk detail yang belum punya embedding
     * (kolomnya masih null, mis. karena gagal saat pertama kali disimpan).
     */
    private function backfillMissingProdukDetailEmbeddings(MasterProdukDetail $produkDetail, ImageEmbeddingService $embeddingService): void
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
                Log::error('Gagal membuat embedding gambar produk (backfill dari menu Barang)', [
                    'id_produk_gambar' => $gambar->id_produk_gambar,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Kalau admin memilih Produk secara manual di tab "Barang Detail" (dropdown master
     * data), pakai pilihan itu apa adanya - namanya TIDAK ditimpa, karena satu Produk
     * bisa dipakai bareng oleh beberapa barang lain. Kalau dikosongkan, jatuh balik ke
     * perilaku lama: auto-buat/auto-sinkron satu Produk yang namanya mengikuti
     * nama_barang punya barang ini sendiri.
     */
    private function syncProduk(Barang $barang, ?int $manualIdProduk, ?int $existingIdProduk): int
    {
        if ($manualIdProduk) {
            return $manualIdProduk;
        }

        if ($existingIdProduk) {
            $produk = MasterProduk::find($existingIdProduk);

            if ($produk) {
                $produk->update(['nama_produk' => $barang->nama_barang]);

                return $produk->id_produk;
            }
        }

        $produk = MasterProduk::create(['nama_produk' => $barang->nama_barang]);

        return $produk->id_produk;
    }

    /**
     * Simpan tab "Barang Detail" (master_produk_detail) yang terhubung ke barang ini
     * lewat kode_barang. Satuan & kategori t_barang (lihat update()) disinkronkan dari
     * sini, bukan diisi manual lagi lewat tab "Barang".
     */
    private function syncProdukDetail(Request $request, Barang $barang, array $detailData, ImageEmbeddingService $embeddingService): MasterProdukDetail
    {
        $produkDetail = MasterProdukDetail::firstOrNew(['kode_barang' => $barang->kode_barang]);

        $idProduk = $this->syncProduk(
            $barang,
            $detailData['id_produk'] ?? null,
            $produkDetail->exists ? $produkDetail->id_produk : null
        );

        $produkDetail->fill([
            'id_produk' => $idProduk,
            'category_id' => $detailData['category_id'] ?? null,
            'kode_barang' => $barang->kode_barang,
            'id_tipe' => $detailData['id_tipe'] ?? null,
            'id_satuan' => $detailData['id_satuan'] ?? null,
            'id_berat' => $detailData['id_berat'] ?? null,
            'id_ukuran' => $detailData['id_ukuran'] ?? null,
            'id_warna' => $detailData['id_warna'] ?? null,
            'id_karakter' => $detailData['id_karakter'] ?? null,
            'id_uom' => $detailData['id_uom'] ?? null,
        ]);

        $produkDetail->save();

        $deletedGambarIds = $detailData['deleted_gambar_ids'] ?? [];

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

        if ($request->hasFile('detail.foto')) {
            foreach ($request->file('detail.foto') as $file) {
                MasterProdukDetailGambar::create(
                    $this->buildProdukDetailGambarData($produkDetail->id_produk_detail, $file, $embeddingService)
                );
            }
        }

        $this->backfillMissingProdukDetailEmbeddings($produkDetail, $embeddingService);

        // Foto Produk wajib diisi (minimal 1), tapi tidak wajib upload ulang setiap
        // kali simpan - cukup ada foto lama yang tersisa setelah proses hapus di atas.
        $totalGambar = MasterProdukDetailGambar::where('id_produk_detail', $produkDetail->id_produk_detail)->count();

        if ($totalGambar === 0) {
            throw ValidationException::withMessages([
                'detail.foto' => 'Minimal satu foto produk wajib diunggah.',
            ]);
        }

        return $produkDetail;
    }

    /**
     * Kolom master_produk_detail yang wajib terisi sebelum boleh disinkron ke m_item.
     */
    private function missingProdukDetailFieldsForSync(MasterProdukDetail $produkDetail): array
    {
        $required = [
            'kode_barang' => $produkDetail->kode_barang,
            'category_id' => $produkDetail->category_id,
            'id_tipe' => $produkDetail->id_tipe,
            'id_satuan' => $produkDetail->id_satuan,
            'id_uom' => $produkDetail->id_uom,
        ];

        return array_keys(array_filter($required, fn($value) => empty($value)));
    }

    /**
     * Kolom harga t_barang yang wajib terisi (bukan kosong/0) sebelum boleh disinkron ke m_item.
     */
    private function missingHargaFieldsForSync(Barang $barang): array
    {
        $required = [
            'harga_beli_before' => $barang->harga_beli_before,
            'harga_beli' => $barang->harga_beli,
            'harga_jual_before' => $barang->harga_jual_before,
            'harga_jual' => $barang->harga_jual,
            'harga_jual_jumbo' => $barang->harga_jual_jumbo,
            'harga_jual_jumbo_before' => $barang->harga_jual_jumbo_before,
        ];

        return array_keys(array_filter($required, fn($value) => $value === null || (float) $value <= 0));
    }

    /**
     * Sinkron otomatis ke m_item begitu semua kolom wajib (produk detail + harga) sudah
     * terisi. Kalau kode_barang ini sudah pernah disinkron sebelumnya, tidak insert lagi -
     * itu sebabnya ini cuma create sekali, bukan updateOrCreate seperti versi lama di
     * menu Produk Detail (fitur itu sudah dipindah & disederhanakan ke sini).
     */
    private function syncMItemIfEligible(Barang $barang, MasterProdukDetail $produkDetail): void
    {
        if (MItem::where('itemcode', $barang->kode_barang)->exists()) {
            return;
        }

        $missingDetail = $this->missingProdukDetailFieldsForSync($produkDetail);
        $missingHarga = $this->missingHargaFieldsForSync($barang);

        if (!empty($missingDetail) || !empty($missingHarga)) {
            return;
        }

        $produkDetail->loadMissing(['category', 'uom']);

        $ppnPercent = (float) (Ppn::where('active', 1)->orderByDesc('id_ppn')->value('persen_ppn') ?? 0);
        $marginPercent = (float) ($produkDetail->category->margin ?? 0);

        $hargaJualSetelahPpn = $barang->harga_jual * (1 + $ppnPercent / 100);
        $sellingPrice = round($hargaJualSetelahPpn * (1 + $marginPercent / 100), 2);

        // endstock = total qty SPK barang ini x qty_pos t_barang. Dijumlah di PHP
        // (bukan SUM() di SQL) karena kolom t_spk.qty bertipe varchar di database.
        $totalSpkQty = Spk::where('id_barang', $barang->id_barang)
            ->pluck('qty')
            ->sum(fn($qty) => (float) $qty);
        $endstock = $totalSpkQty * (float) $barang->qty_pos;

        MItem::create([
            'itemcode' => $barang->kode_barang,
            'itemcodeint' => $barang->kode_barang,
            'itemcodeint1' => $barang->kode_barang,
            'barcode1' => $barang->kode_barang,
            'barcode2' => $barang->kode_barang,
            'itemname' => $barang->nama_barang,
            'itemname1' => $barang->nama_barang,
            'categorycode' => $produkDetail->category_id,
            'uom' => $produkDetail->uom->nama_uom ?? null,
            'minstock' => $barang->min_stok,
            'maxstock' => $barang->max_stok,
            'endstock' => $endstock,
            'buyingprice' => $barang->harga_beli,
            'lastbuyingprice' => $barang->harga_beli_before,
            'sellingprice' => $sellingPrice,
            'sellingpricealt' => $sellingPrice,
            'nonaktif' => $barang->active ? 0 : 1,
            'canbesold' => $barang->active ? 1 : 0,
        ]);
    }

    /**
     * Update kolom PPN pada semua baris t_spk milik barang ini (dicocokkan lewat
     * id_barang). id_ppn & ppn_persen diambil dari m_ppn yang sedang aktif - ppn_persen
     * disimpan apa adanya (mis. 11.00, sama seperti m_ppn.persen_ppn). Pembagian per
     * 100 hanya dipakai saat menghitung nilai_ppn dalam rupiah, bukan pada kolom
     * ppn_persen itu sendiri.
     */
    private function syncSpkPpnFields(Barang $barang): void
    {
        $ppn = Ppn::where('active', 1)->orderByDesc('id_ppn')->first();

        $idPpn = $ppn?->id_ppn;
        $ppnPersen = $ppn ? (float) $ppn->persen_ppn : 0;

        Spk::where('id_barang', $barang->id_barang)->get()->each(function (Spk $spk) use ($idPpn, $ppnPersen, $barang) {
            $hargaJualDpp = (float) $spk->harga_jual;

            // Baris SPK yang dibuat sebelum barang ini punya harga (harga_jual masih
            // "0") jadi tidak punya dasar hitung PPN - pakai harga_jual t_barang yang
            // sekarang sebagai fallback, sekaligus backfill kolom harga_jual di SPK-nya.
            if ($hargaJualDpp <= 0) {
                $hargaJualDpp = (float) $barang->harga_jual;
            }

            $nilaiPpn = ($ppnPersen / 100) * $hargaJualDpp;

            $spk->update([
                'harga_jual' => $hargaJualDpp,
                'id_ppn' => $idPpn,
                'ppn_persen' => $ppnPersen,
                'harga_jual_dpp' => $hargaJualDpp,
                'nilai_ppn' => $nilaiPpn,
                'harga_jual_include_ppn' => $hargaJualDpp + $nilaiPpn,
            ]);
        });
    }

    public function update(Request $request, $id, ImageEmbeddingService $embeddingService)
    {
        $barang = Barang::with(['details.gambars', 'produk'])->findOrFail($id);

        $validated = $request->validate([
            'nama_barang' => 'required|string|max:255',
            'harga_beli' => 'required|numeric',
            'harga_beli_before' => 'required|numeric',
            'harga_jual' => 'required|numeric',
            'harga_jual_before' => 'required|numeric',
            'harga_jual_jumbo' => 'required|numeric',
            'harga_jual_jumbo_before' => 'required|numeric',
            'stok' => 'required|integer',
            'qty_pos' => 'required|integer',
            'min_stok' => 'required|integer',
            'max_stok' => 'required|integer',

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

            // Semua wajib diisi di tab "Barang Detail", kecuali Produk, Warna, Karakter, Berat & Ukuran.
            // Produk boleh dikosongkan - kalau kosong akan dibuatkan/disinkron otomatis dari Nama Barang.
            'detail.id_produk' => 'nullable|integer|exists:master_produk,id_produk',
            'detail.category_id' => 'required|string|max:20|exists:m_category_2026,categorycode',
            'detail.id_tipe' => 'required|integer|exists:master_tipe,id_tipe',
            'detail.id_satuan' => 'required|integer|exists:master_satuan,id_satuan',
            'detail.id_berat' => 'nullable|integer|exists:master_berat,id_berat',
            'detail.id_ukuran' => 'nullable|integer|exists:master_ukuran,id_ukuran',
            'detail.id_warna' => 'nullable|integer|exists:master_warna,id_warna',
            'detail.id_karakter' => 'nullable|integer|exists:master_karakter,id_karakter',
            'detail.id_uom' => 'required|integer|exists:master_uom,id_uom',

            'detail.foto' => 'nullable|array',
            'detail.foto.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            'detail.deleted_gambar_ids' => 'nullable|array',
            'detail.deleted_gambar_ids.*' => 'integer',
        ]);

        DB::transaction(function () use ($request, $validated, $barang, $embeddingService) {
            $hargaBeli = $validated['harga_beli'] ?? 0;
            $hargaJual = $validated['harga_jual'] ?? 0;
            $hargaJualJumbo = $validated['harga_jual_jumbo'] ?? 0;

            $detailData = $validated['detail'];

            // Satuan & kategori t_barang tidak lagi diisi manual di tab "Barang" -
            // nilainya disinkron dari pilihan di tab "Barang Detail". Satuan disimpan
            // sebagai nilai/nama-nya (bukan id_satuan), karena kolom t_barang.satuan
            // memang berupa string, bukan foreign key.
            $satuanNama = !empty($detailData['id_satuan'])
                ? MasterSatuan::find($detailData['id_satuan'])?->nama
                : null;

            $barang->update([
                'nama_barang' => $validated['nama_barang'],
                'harga_beli' => $hargaBeli,
                'harga_beli_before' => $validated['harga_beli_before'] ?? 0,
                'harga_jual' => $hargaJual,
                'harga_jual_before' => $validated['harga_jual_before'] ?? 0,
                'harga_jual_jumbo' => $hargaJualJumbo,
                'harga_jual_jumbo_before' => $validated['harga_jual_jumbo_before'] ?? 0,
                'margin' => $hargaJual - $hargaBeli,
                'satuan' => $satuanNama,
                'stok' => $validated['stok'] ?? 0,
                'qty_pos' => $validated['qty_pos'] ?? 0,
                'min_stok' => $validated['min_stok'] ?? 0,
                'max_stok' => $validated['max_stok'] ?? 0,
                'category_code' => $detailData['category_id'] ?? null,
                'min_vendor' => 0,
                'min_cabang' => 0,
                'kirim_langsung' => 0,
                'modified_by' => auth()->id(),
                'modified_date' => now(),
            ]);

            $produkDetail = $this->syncProdukDetail($request, $barang, $detailData, $embeddingService);

            $this->syncMItemIfEligible($barang, $produkDetail);
            $this->syncSpkPpnFields($barang);

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

        return back()->with('success', 'Barang berhasil diupdate');
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

        return back()->with('success', 'Barang berhasil dihapus');
    }
}