<?php

namespace App\Http\Controllers;

use App\Models\MasterUom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterUomController extends Controller
{
    public function index(Request $request)
    {
        $uom = MasterUom::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_uom', 'like', "%{$search}%")
                        ->orWhere('deskripsi', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id_uom')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Uom/Index', [
            'uom' => $uom,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_uom' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        MasterUom::create($validated);

        return back()->with('success', 'UOM berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $uom = MasterUom::findOrFail($id);

        $validated = $request->validate([
            'nama_uom' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ]);

        $uom->update($validated);

        return back()->with('success', 'UOM berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterUom::findOrFail($id)->delete();

        return back()->with('success', 'UOM berhasil dihapus');
    }
}
