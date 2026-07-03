<?php

namespace App\Http\Controllers;

use App\Models\Divisi;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DivisiController extends Controller
{
    public function index(Request $request)
{
    $divisi = Divisi::query()
        ->when($request->search, fn($q, $s) => $q->where('divisi_name', 'like', "%{$s}%"))
        ->paginate($request->per_page ?? 10)
        ->withQueryString();

    return Inertia::render('Divisi/Index', [
        'divisi' => $divisi, 
        'filters' => $request->only(['search', 'per_page'])
    ]);
}

    public function store(Request $request)
    {
        $validated = $request->validate(['divisi_name' => 'required|string|max:255']);
        Divisi::create($validated);
        return redirect()->back()->with('success', 'Divisi berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate(['divisi_name' => 'required|string|max:255']);
        Divisi::findOrFail($id)->update($validated);
        return redirect()->back()->with('success', 'Divisi berhasil diupdate');
    }

    public function destroy($id)
    {
        Divisi::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Divisi berhasil dihapus');
    }
}