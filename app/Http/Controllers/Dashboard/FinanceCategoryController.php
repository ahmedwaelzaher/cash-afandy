<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Redot\Http\Controllers\Controller;

class FinanceCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.finance-categories.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.finance-categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', new Enum(TransactionType::class)],
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|hex_color',
        ]);

        FinanceCategory::create($validated);

        return $this->created(__('Finance Category'), 'dashboard.finance-categories.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FinanceCategory $financeCategory)
    {
        abort_unless($financeCategory->isDefault(), 404);

        return view('dashboard.finance-categories.edit', [
            'financeCategory' => $financeCategory,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinanceCategory $financeCategory)
    {
        abort_unless($financeCategory->isDefault(), 404);

        $validated = $request->validate([
            'type' => ['required', new Enum(TransactionType::class)],
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'color' => 'nullable|hex_color',
        ]);

        $financeCategory->update($validated);

        return $this->updated(__('Finance Category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinanceCategory $financeCategory)
    {
        abort_unless($financeCategory->isDefault(), 404);

        $financeCategory->delete();

        return $this->deleted(__('Finance Category'));
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(FinanceCategory $financeCategory)
    {
        abort_unless($financeCategory->isDefault(), 404);

        $financeCategory->restore();

        return $this->restored(__('Finance Category'));
    }
}
