<?php

namespace App\Http\Controllers;

use App\Models\MasterSatuan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterSatuanController extends Controller
{
    public function index(Request $request)
    {
        $satuan = MasterSatuan::query()
            ->when($request->search, fn($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('id_satuan')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Satuan/Index', [
            'satuan' => $satuan,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterSatuan::create($validated);
        return redirect()->back()->with('success', 'Satuan berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterSatuan::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Satuan berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterSatuan::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Satuan berhasil dihapus');
    }
}
