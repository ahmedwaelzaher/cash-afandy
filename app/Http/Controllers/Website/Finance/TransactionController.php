<?php

namespace App\Http\Controllers\Website\Finance;

use App\Models\FinanceCategory;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = Transaction::withDefaultPeriod($request->validate(Transaction::filterRules()));

        $query = Transaction::query()->whereBelongsTo($request->user())->filter($filters);

        // Totals first, paginating changes the query in place.
        $totals = Transaction::totals($query);

        return view('website.finance.transactions.index', [
            'transactions' => $query->with('category')->latest('occurred_on')->latest('id')->paginate(20)->withQueryString(),
            'totals' => $totals,
            'filters' => $filters,
            'categories' => $this->categories($request),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate(Transaction::rules($request->user()));

        // The type always follows the category, so the two never disagree.
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;

        $request->user()->transactions()->create($validated);

        return $this->created(__('Transaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        return view('website.finance.transactions.edit', [
            'transaction' => $transaction,
            'categories' => $this->categories($request),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        $validated = $request->validate(Transaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;

        $transaction->update($validated);

        return $this->updated(__('Transaction'), 'website.finance.transactions.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        $transaction->delete();

        return $this->deleted(__('Transaction'));
    }

    /**
     * Get the categories available to the user, grouped by type.
     */
    protected function categories(Request $request)
    {
        return FinanceCategory::availableTo($request->user())
            ->orderByRaw('user_id is null')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (FinanceCategory $category) => $category->type->value);
    }
}
