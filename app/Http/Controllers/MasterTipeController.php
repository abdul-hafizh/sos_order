<?php

namespace App\Http\Controllers;

use App\Models\MasterTipe;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterTipeController extends Controller
{
    public function index(Request $request)
    {
        $tipe = MasterTipe::query()
            ->when($request->search, fn($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('id_tipe')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Tipe/Index', [
            'tipe' => $tipe,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterTipe::create($validated);
        return redirect()->back()->with('success', 'Type berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterTipe::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Type berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterTipe::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Type berhasil dihapus');
    }
}
