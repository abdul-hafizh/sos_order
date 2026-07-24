<?php

namespace App\Http\Controllers;

use App\Models\MasterWarna;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterWarnaController extends Controller
{
    public function index(Request $request)
    {
        $warna = MasterWarna::query()
            ->when($request->search, fn($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('id_warna')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Warna/Index', [
            'warna' => $warna,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterWarna::create($validated);
        return redirect()->back()->with('success', 'Warna berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterWarna::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Warna berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterWarna::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Warna berhasil dihapus');
    }
}
