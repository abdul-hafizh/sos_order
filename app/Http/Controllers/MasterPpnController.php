<?php

namespace App\Http\Controllers;

use App\Models\Ppn;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MasterPpnController extends Controller
{
    public function index(Request $request)
    {
        $ppn = Ppn::query()
            ->when($request->search, function ($query, $search) {
                $query->where('kode_ppn', 'like', "%{$search}%")
                    ->orWhere('nama_ppn', 'like', "%{$search}%");
            })
            ->orderByDesc('id_ppn')
            ->paginate($request->per_page ?? 10)
            ->withQueryString();

        return Inertia::render('Master-Ppn/Index', [
            'ppn' => $ppn,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    private function rules(?int $id = null): array
    {
        return [
            'kode_ppn' => ['required', 'string', 'max:20', Rule::unique('m_ppn', 'kode_ppn')->ignore($id, 'id_ppn')],
            'nama_ppn' => 'required|string|max:50',
            'persen_ppn' => 'nullable|numeric|min:0|max:100',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'active' => 'boolean',
            'keterangan' => 'nullable|string|max:255',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        Ppn::create([
            ...$validated,
            'active' => $validated['active'] ?? true,
            'modified_by' => auth()->id(),
            'modified_date' => now(),
        ]);

        return back()->with('success', 'PPN berhasil ditambah');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->rules((int) $id));

        Ppn::findOrFail($id)->update([
            ...$validated,
            'active' => $validated['active'] ?? true,
            'modified_by' => auth()->id(),
            'modified_date' => now(),
        ]);

        return back()->with('success', 'PPN berhasil diupdate');
    }

    public function destroy($id)
    {
        Ppn::findOrFail($id)->delete();

        return back()->with('success', 'PPN berhasil dihapus');
    }
}
