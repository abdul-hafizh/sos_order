<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Cabang;
use App\Models\Menu;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();


        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('user', 'like', "%{$search}%")
                ->orWhere('nama_user', 'like', "%{$search}%");
        }


        $sortField = $request->input('sort', 'user');
        $sortOrder = $request->input('order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        return Inertia::render('Users/Index', [
            'users'   => $query->paginate($request->input('per_page', 10))->withQueryString(),
            'cabangs' => Cabang::select('kode_cabang', 'cabang_nama')
                ->orderBy('cabang_nama')
                ->get(),
            'menus'   => Menu::orderBy('menu_order')->get(),
            'filters' => $request->only(['search', 'sort', 'order', 'per_page'])
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'user'        => 'required|unique:t_user,user',
            'nama_user'   => 'required',
            'email'       => 'required|email',
            'pwd'         => 'required|min:4|same:konf_pwd',
            'kode_cabang' => 'required',
            'list_menu'   => 'array'
        ]);

        $user = new User();
        $user->user = $request->user;
        $user->nama_user = $request->nama_user;
        $user->email = $request->email;
        $user->type_user = $request->type_user;
        $user->kode_cabang = $request->kode_cabang;

        $user->pwd = md5(strtolower($request->user) . strtolower($request->pwd));

        $user->list_menu = !empty($request->list_menu) ? implode('#', $request->list_menu) . '#' : '';

        $user->created_by = Auth::id() ?? 1;
        $user->modified_date = now();
        $user->save();

        return redirect()->back()->with('success', 'User berhasil ditambahkan.');
    }


    public function update(Request $request, User $user)
    {
        $request->validate([
            'user'        => 'required|unique:t_user,user,' . $user->id, 
            'nama_user'   => 'required',
            'email'       => 'required|email',
            'kode_cabang' => 'required',
            'list_menu'   => 'array'
        ]);

        $user->user = $request->user;
        $user->nama_user = $request->nama_user;
        $user->email = $request->email;
        $user->type_user = $request->type_user;
        $user->kode_cabang = $request->kode_cabang;

        if ($request->filled('pwd')) {
            $request->validate(['pwd' => 'min:4|same:konf_pwd']);
            $user->pwd = md5(strtolower($request->user) . strtolower($request->pwd));
        }

        $user->list_menu = !empty($request->list_menu) ? implode('#', $request->list_menu) . '#' : '';
        $user->modified_date = now();
        $user->save();

        return redirect()->back()->with('success', 'User berhasil diupdate.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}
