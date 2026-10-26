<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Category;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.categories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'slug' => 'required|string|alpha_dash|unique:categories,slug',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'icon' => 'required|string|max:255',
        ]);

        Category::create($validated);

        return $this->created(__('Category'), 'dashboard.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return view('dashboard.categories.edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'slug' => 'required|string|alpha_dash|unique:categories,slug,' . $category->id,
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'icon' => 'required|string|max:255',
        ]);

        $category->update($validated);

        return $this->updated(__('Category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();

        return $this->deleted(__('Category'));
    }
}
