<?php

namespace App\Http\Controllers\Api\Website\Finance;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Redot\Http\Controllers\Controller;

class CategoryController extends Controller
{
    /**
     * Display the categories available to the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', new Enum(TransactionType::class)],
        ]);

        $categories = FinanceCategory::availableTo($request->user())
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->orderByRaw('user_id is null desc')
            ->latest('id')
            ->get();

        return $this->respond($categories);
    }

    /**
     * Store a new category for the current user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', new Enum(TransactionType::class)],
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|hex_color',
        ]);

        $validated['title'] = array_fill_keys(setting('website_locales'), $validated['title']);

        $category = $request->user()->financeCategories()->create($validated);

        return $this->respond($category, code: 201);
    }

    /**
     * Update the given category of the current user.
     */
    public function update(Request $request, FinanceCategory $category): JsonResponse
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

        return $this->respond($category);
    }

    /**
     * Delete the given category of the current user.
     */
    public function destroy(Request $request, FinanceCategory $category): JsonResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $category->delete();

        return $this->respond();
    }
}
