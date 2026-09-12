<?php

namespace App\Http\Controllers;

use App\Models\ChannelCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = ChannelCategory::withCount('channels')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:channel_categories,name'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        ChannelCategory::create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'],
            'active' => true,
        ]);

        return back()->with('ok', 'Categoría creada.');
    }

    public function update(Request $request, ChannelCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'],
            'active' => (bool) ($data['active'] ?? false),
        ]);

        return back()->with('ok', 'Categoría actualizada.');
    }
}
