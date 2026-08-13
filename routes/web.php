<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\SpkController;
use App\Http\Controllers\MasterBarangController;
use App\Http\Controllers\BarangVendorController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MasterKategoriController;
use App\Http\Controllers\MasterTipeController;
use App\Http\Controllers\MasterSatuanController;
use App\Http\Controllers\MasterBeratController;
use App\Http\Controllers\MasterUkuranController;
use App\Http\Controllers\MasterWarnaController;
use App\Http\Controllers\MasterKarakterController;
use App\Http\Controllers\MasterProdukController;
use App\Http\Controllers\MasterProdukDetailController;
use App\Http\Controllers\MasterUomController;
use App\Http\Controllers\AdminHoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Route Publik
Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', [BarangController::class, 'dashboard'])->name('dashboard');
    Route::post('/dashboard/search-image', [BarangController::class, 'searchByImage'])->name('dashboard.search-image');

    Route::post('/keranjang/barang', [KeranjangController::class, 'storeBarang'])
        ->name('keranjang.storeBarang');
    Route::post('/keranjang/barang-banyak', [KeranjangController::class, 'storeBarangBanyak'])
        ->name('keranjang.storeBarangBanyak');
    Route::post('/keranjang/barang-baru', [KeranjangController::class, 'storeBarangBaru'])
        ->name('keranjang.storeBarangBaru');
    Route::put('/keranjang/{id}/qty', [KeranjangController::class, 'updateQty'])
        ->name('keranjang.updateQty');
    Route::delete('/keranjang/{id}', [KeranjangController::class, 'destroy'])
        ->name('keranjang.destroy');
    Route::put('/keranjang/{id}/update-nama-barang-baru', [KeranjangController::class, 'updateNamaBarangBaru'])
        ->name('keranjang.updateNamaBarangBaru');
    Route::post('/keranjang/pesan-sekarang', [KeranjangController::class, 'pesanSekarang'])
        ->name('keranjang.pesanSekarang');
    Route::post(
        '/keranjang/{id}/gambar',
        [KeranjangController::class, 'uploadGambar']
    )->name('keranjang.uploadGambar');

    Route::get('/spk', [SpkController::class, 'index'])->name('spk.index');
    Route::get('/spk/{id}', [SpkController::class, 'show'])->name('spk.show');
    Route::put('/spk/{id}/validasi', [SpkController::class, 'updateValidasi'])->name('spk.updateValidasi');
    Route::put('/spk/{id}/kirim', [SpkController::class, 'updateKirim'])->name('spk.updateKirim');
    Route::put('/spk/{id}/terima', [SpkController::class, 'updateTerima'])->name('spk.updateTerima');
    Route::put('/spk/{id}/ketersediaan', [SpkController::class, 'updateKetersediaan'])->name('spk.updateKetersediaan');

    Route::middleware('ho.user')->group(function () {
        Route::get('/admin-ho', [AdminHoController::class, 'index'])->name('admin-ho.index');
        Route::put('/admin-ho/{user}/toggle', [AdminHoController::class, 'toggle'])->name('admin-ho.toggle');
        Route::put('/admin-ho/{user}/telegram', [AdminHoController::class, 'updateTelegram'])->name('admin-ho.updateTelegram');
    });

    Route::middleware('admin.ho')->group(function () {
        Route::get('/procure', fn() => Inertia::render('Procure'))->name('procure');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::resource('produk', ProdukController::class);
        Route::resource('divisi', DivisiController::class);
        Route::resource('vendor', VendorController::class);
        Route::resource('barang', BarangController::class);
        Route::resource('master-barang', MasterBarangController::class);

        Route::resource('master-kategori', MasterKategoriController::class)->only(['index', 'update']);

        Route::resource('master-tipe', MasterTipeController::class);
        Route::resource('master-satuan', MasterSatuanController::class);
        Route::resource('master-berat', MasterBeratController::class);
        Route::resource('master-ukuran', MasterUkuranController::class);
        Route::resource('master-warna', MasterWarnaController::class);
        Route::resource('master-karakter', MasterKarakterController::class);

        Route::resource('master-produk', MasterProdukController::class);

        Route::get('master-produk-detail-barang-options', [MasterProdukDetailController::class, 'barangOptions'])
            ->name('master-produk-detail.barang-options');
        Route::resource('master-produk-detail', MasterProdukDetailController::class);

        Route::resource('master-uom', MasterUomController::class);

        Route::prefix('barang-vendor')
            ->name('barang-vendor.')
            ->group(function () {

                Route::get('/vendor-list', [BarangVendorController::class, 'vendorList'])
                    ->name('vendor-list');

                Route::get('/{kode_barang}', [BarangVendorController::class, 'index'])
                    ->name('index');

                Route::post('/', [BarangVendorController::class, 'store'])
                    ->name('store');

                Route::put('/{id_barang_vendor}', [BarangVendorController::class, 'update'])
                    ->name('update');

                Route::delete('/{id_barang_vendor}', [BarangVendorController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::prefix('category')->name('category.')->group(function () {

            Route::get('/list', [CategoryController::class, 'list'])
                ->name('list');

            Route::get('/search', [CategoryController::class, 'search'])
                ->name('search');

            Route::get('/{categorycode}', [CategoryController::class, 'show'])
                ->name('show');

        });
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__ . '/auth.php';