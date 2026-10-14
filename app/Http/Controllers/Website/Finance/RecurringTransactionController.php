<?php

namespace App\Http\Controllers\Website\Finance;

use App\Models\FinanceCategory;
use App\Models\RecurringTransaction;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class RecurringTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $recurring = $request->user()->recurringTransactions()
            ->with('category')
            ->orderByDesc('is_active')
            ->orderByRaw('next_due_on is null')
            ->orderBy('next_due_on')
            ->get()
            ->groupBy(fn (RecurringTransaction $item) => $item->type->value);

        return view('website.finance.recurring.index', [
            'recurring' => $recurring,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return view('website.finance.recurring.create', [
            'categories' => FinanceCategory::groupedFor($request->user()),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(RecurringTransaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;
        $validated['is_active'] = $request->boolean('is_active');

        $recurring = $request->user()->recurringTransactions()->make($validated);
        $recurring->schedule()->save();

        // Anything due today shows up right away, without waiting for the nightly run.
        $recurring->postDue();

        return $this->created(__('Recurring Item'), 'website.finance.recurring.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === $request->user()->id, 404);

        return view('website.finance.recurring.edit', [
            'recurring' => $recurring,
            'categories' => FinanceCategory::groupedFor($request->user()),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === $request->user()->id, 404);

        $validated = $request->validate(RecurringTransaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;
        $validated['is_active'] = $request->boolean('is_active');

        $recurring->fill($validated)->schedule()->save();
        $recurring->postDue();

        return $this->updated(__('Recurring Item'), 'website.finance.recurring.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, RecurringTransaction $recurring)
    {
        abort_unless($recurring->user_id === $request->user()->id, 404);

        // Already posted transactions stay, they are part of the user's history.
        $recurring->delete();

        return $this->deleted(__('Recurring Item'), 'website.finance.recurring.index');
    }
}
