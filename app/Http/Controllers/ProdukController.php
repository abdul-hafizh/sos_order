<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $produk = Produk::query()
            ->when($request->search, fn($q, $s) => $q->where('nama_produk', 'like', "%{$s}%"))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Produk/Index', [
            'produk' => $produk,
            'list_divisi' => \App\Models\Divisi::all(),
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_produk' => 'required',
            'nama_produk' => 'required',
            'harga_produk' => 'required|numeric',
            'divisi' => 'required',
            'aktif' => 'boolean'
        ]);

        Produk::create($validated);
        return redirect()->back()->with('success', 'Produk berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $produk = Produk::findOrFail($id);
        $validated = $request->validate([
            'kode_produk' => 'required',
            'nama_produk' => 'required',
            'harga_produk' => 'required|numeric',
        ]);

        $produk->update($validated);
        return redirect()->back()->with('success', 'Produk berhasil diupdate');
    }

    public function destroy($id)
    {
        Produk::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Produk berhasil dihapus');
    }
}
