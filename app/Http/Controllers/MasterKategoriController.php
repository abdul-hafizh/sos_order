<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MasterKategoriController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->search, fn($q, $s) => $q->where('categoryname', 'like', "%{$s}%"))
            ->orderByRaw('CASE WHEN urutan IS NULL THEN 1 ELSE 0 END')
            ->orderBy('urutan')
            ->orderBy('categoryname')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Kategori/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function update(Request $request, $categorycode)
    {
        $category = Category::findOrFail($categorycode);

        $validated = $request->validate([
            'gambar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'active' => 'nullable|boolean',
            'urutan' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('gambar')) {
            if (!empty($category->gambar)) {
                Storage::disk('public')->delete($category->gambar);
            }

            $validated['gambar'] = $request->file('gambar')->store('kategori', 'public');
        } else {
            // Tidak ada file baru diupload - jangan timpa gambar yang sudah ada
            // dengan null (form selalu mengirim key 'gambar' meski kosong).
            unset($validated['gambar']);
        }

        $category->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui');
    }
}
