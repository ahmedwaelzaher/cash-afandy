<?php

namespace App\Http\Controllers\Website\Finance;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Redot\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $categories = FinanceCategory::availableTo($request->user())
            ->orderByRaw('user_id is null desc')
            ->latest('id')
            ->get()
            ->groupBy(fn (FinanceCategory $category) => $category->type->value);

        return view('website.finance.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('website.finance.categories.create', [
            'type' => $request->query('type'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', new Enum(TransactionType::class)],
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|hex_color',
        ]);

        // User categories carry a single title, stored for every locale so it always shows.
        $validated['title'] = array_fill_keys(setting('website_locales'), $validated['title']);

        $request->user()->financeCategories()->create($validated);

        return $this->created(__('Category'), 'website.finance.categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, FinanceCategory $category)
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        return view('website.finance.categories.edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinanceCategory $category)
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'type' => ['required', new Enum(TransactionType::class)],
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|hex_color',
        ]);

        $validated['title'] = array_fill_keys(setting('website_locales'), $validated['title']);

        $category->update($validated);

        return $this->updated(__('Category'), 'website.finance.categories.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, FinanceCategory $category)
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $category->delete();

        return $this->deleted(__('Category'), 'website.finance.categories.index');
    }
}
