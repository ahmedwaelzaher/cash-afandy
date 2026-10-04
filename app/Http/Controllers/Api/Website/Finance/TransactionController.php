<?php

namespace App\Http\Controllers\Api\Website\Finance;

use App\Models\FinanceCategory;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class TransactionController extends Controller
{
    /**
     * Display the current user's transactions with the totals of the filtered period.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = Transaction::withDefaultPeriod($request->validate(Transaction::filterRules()));

        $query = Transaction::query()->whereBelongsTo($request->user())->filter($filters);

        // Totals first, paginating changes the query in place.
        $totals = Transaction::totals($query);

        return $this->respond([
            'filters' => $filters,
            'totals' => $totals,
            'transactions' => $query->with('category')->latest('occurred_on')->latest('id')->paginate(20),
        ]);
    }

    /**
     * Store a new transaction for the current user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(Transaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;

        $transaction = $request->user()->transactions()->create($validated);

        return $this->respond($transaction->load('category'), code: 201);
    }

    /**
     * Update the given transaction of the current user.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        $validated = $request->validate(Transaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;

        $transaction->update($validated);

        return $this->respond($transaction->load('category'));
    }

    /**
     * Delete the given transaction of the current user.
     */
    public function destroy(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 404);

        $transaction->delete();

        return $this->respond();
    }
}
