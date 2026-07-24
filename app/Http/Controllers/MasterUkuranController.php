<?php

namespace App\Http\Controllers;

use App\Models\MasterUkuran;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterUkuranController extends Controller
{
    public function index(Request $request)
    {
        $ukuran = MasterUkuran::query()
            ->when($request->search, fn($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('id_ukuran')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Ukuran/Index', [
            'ukuran' => $ukuran,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterUkuran::create($validated);
        return redirect()->back()->with('success', 'Ukuran berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterUkuran::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Ukuran berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterUkuran::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Ukuran berhasil dihapus');
    }
}
