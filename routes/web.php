<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\DivisiController; 
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\KeranjangController;
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

    Route::get('/procure', fn () => Inertia::render('Procure'))->name('procure');
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

require __DIR__.'/auth.php';