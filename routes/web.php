<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\SpkController;
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
    Route::post('/spk/{id}/buat-master-barang', [SpkController::class, 'buatMasterBarang'])->name('spk.buatMasterBarang');
    Route::put('/spk/{id}/ketersediaan', [SpkController::class, 'updateKetersediaan'])->name('spk.updateKetersediaan');

    Route::get('/procure', fn() => Inertia::render('Procure'))->name('procure');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::resource('produk', ProdukController::class);
    Route::resource('divisi', DivisiController::class);
    Route::resource('vendor', VendorController::class);
    Route::resource('barang', BarangController::class);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';