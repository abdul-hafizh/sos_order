<?php

namespace App\Http\Controllers;

use App\Models\MasterKarakter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterKarakterController extends Controller
{
    public function index(Request $request)
    {
        $karakter = MasterKarakter::query()
            ->when($request->search, fn($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('id_karakter')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Karakter/Index', [
            'karakter' => $karakter,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterKarakter::create($validated);
        return redirect()->back()->with('success', 'Karakter berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterKarakter::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Karakter berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterKarakter::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Karakter berhasil dihapus');
    }
}
