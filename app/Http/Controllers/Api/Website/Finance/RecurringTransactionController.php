<?php

namespace App\Http\Controllers\Api\Website\Finance;

use App\Models\FinanceCategory;
use App\Models\RecurringTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class RecurringTransactionController extends Controller
{
    /**
     * Display the current user's recurring income and expenses.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->respond(
            $request->user()->recurringTransactions()->with('category')->orderBy('next_due_on')->get()
        );
    }

    /**
     * Store a new recurring income or expense for the current user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate(RecurringTransaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;
        $validated['is_active'] = $request->boolean('is_active', true);

        $recurring = $request->user()->recurringTransactions()->make($validated);
        $recurring->schedule()->save();
        $recurring->postDue();

        return $this->respond($recurring->load('category'), code: 201);
    }

    /**
     * Update the given recurring income or expense of the current user.
     */
    public function update(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        abort_unless($recurring->user_id === $request->user()->id, 404);

        $validated = $request->validate(RecurringTransaction::rules($request->user()));
        $validated['type'] = FinanceCategory::find($validated['finance_category_id'])->type;
        $validated['is_active'] = $request->boolean('is_active', $recurring->is_active);

        $recurring->fill($validated)->schedule()->save();
        $recurring->postDue();

        return $this->respond($recurring->load('category'));
    }

    /**
     * Delete the given recurring income or expense, its posted transactions stay.
     */
    public function destroy(Request $request, RecurringTransaction $recurring): JsonResponse
    {
        abort_unless($recurring->user_id === $request->user()->id, 404);

        $recurring->delete();

        return $this->respond();
    }
}
