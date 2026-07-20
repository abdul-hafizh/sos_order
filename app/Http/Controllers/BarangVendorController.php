<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangVendor;
use App\Models\Vendor;
use Illuminate\Http\Request;

class BarangVendorController extends Controller
{
    public function index($kode_barang)
    {
        $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();

        $vendors = BarangVendor::with('vendor')
            ->where('kode_barang', $kode_barang)
            ->orderBy('id_barang_vendor')
            ->get();

        return response()->json([
            'barang' => $barang,
            'vendors' => $vendors,
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => 'required|exists:t_barang,kode_barang',
            'kode_vendor' => 'required|exists:t_vendor,kode_vendor',
            'active' => 'boolean',
        ]);

        $exists = BarangVendor::where('kode_barang', $validated['kode_barang'])
            ->where('kode_vendor', $validated['kode_vendor'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'kode_vendor' => 'Vendor sudah terdaftar pada barang ini.',
            ]);
        }

        BarangVendor::create([
            'kode_barang' => $validated['kode_barang'],
            'kode_vendor' => $validated['kode_vendor'],
            'active' => $validated['active'] ?? true,
        ]);

        return back()->with('message', 'Vendor berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $barangVendor = BarangVendor::findOrFail($id);

        $validated = $request->validate([
            'active' => 'required|boolean',
        ]);

        $barangVendor->update($validated);

        return back()->with('message', 'Data vendor berhasil diupdate.');
    }

    public function destroy($id)
    {
        $barangVendor = BarangVendor::findOrFail($id);

        $barangVendor->delete();

        return back()->with('message', 'Vendor berhasil dihapus dari barang.');
    }

    public function vendorList()
    {
        return Vendor::orderBy('nama_vendor')
            ->get([
                'kode_vendor',
                'nama_vendor'
            ]);
    }
}