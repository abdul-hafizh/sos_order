<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function list()
    {
        return Category::orderBy('categoryname')
            ->get();
    }
    public function search(Request $request)
    {
        $categories = Category::query()
            ->when($request->search, function ($query, $search) {
                $query->where('categorycode', 'like', "%{$search}%")
                      ->orWhere('categoryname', 'like', "%{$search}%");
            })
            ->orderBy('categoryname')
            ->get();

        return response()->json($categories);
    }

    public function show($categorycode)
    {
        $category = Category::findOrFail($categorycode);

        return response()->json($category);
    }
}