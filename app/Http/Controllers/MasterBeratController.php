<?php

namespace App\Http\Controllers;

use App\Models\MasterBerat;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterBeratController extends Controller
{
    public function index(Request $request)
    {
        $berat = MasterBerat::query()
            ->when($request->search, fn($q, $s) => $q->where('nama', 'like', "%{$s}%"))
            ->orderByDesc('id_berat')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Berat/Index', [
            'berat' => $berat,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterBerat::create($validated);
        return redirect()->back()->with('success', 'Berat berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['nama' => 'required|string|max:255']);
        MasterBerat::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Berat berhasil diupdate');
    }

    public function destroy($id)
    {
        MasterBerat::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Berat berhasil dihapus');
    }
}
