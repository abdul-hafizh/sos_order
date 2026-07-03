<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::query()
            ->when($request->search, fn($q, $s) => $q->where('nama_vendor', 'like', "%{$s}%"))
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Vendor/Index', [
            'vendors' => $vendors,
            'filters' => $request->only(['search', 'per_page'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_vendor' => 'required|unique:t_vendor',
            'nama_vendor' => 'required',
            'alamat' => 'nullable',
            'telp' => 'nullable',
            'pic' => 'nullable',
            'kota' => 'nullable',
        ]);

        Vendor::create($validated);
        return redirect()->back()->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        $validated = $request->validate([
            'kode_vendor' => 'required',
            'nama_vendor' => 'required',
            'alamat' => 'nullable',
            'telp' => 'nullable',
            'pic' => 'nullable',
            'kota' => 'nullable',
        ]);

        $vendor->update($validated);
        return redirect()->back()->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Vendor::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Vendor berhasil dihapus.');
    }
}