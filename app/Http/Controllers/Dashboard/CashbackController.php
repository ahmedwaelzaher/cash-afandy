<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Cashback;
use App\Models\Country;
use App\Models\Representative;
use App\Models\Store;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class CashbackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.cashbacks.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.cashbacks.create', [
            'stores' => Store::query(),
            'countries' => Country::query(),
            'representatives' => Representative::query(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validated($request);

        Cashback::create($validated);

        return $this->created(__('Cashback'), 'dashboard.cashbacks.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cashback $cashback)
    {
        return view('dashboard.cashbacks.edit', [
            'cashback' => $cashback,
            'stores' => Store::query(),
            'countries' => Country::query(),
            'representatives' => Representative::query(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cashback $cashback)
    {
        $validated = $this->validated($request);

        $cashback->update($validated);

        return $this->updated(__('Cashback'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cashback $cashback)
    {
        $cashback->delete();

        return $this->deleted(__('Cashback'));
    }

    /**
     * Validate the request for storing/updating a cashback.
     */
    protected function validated(Request $request): array
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'percentage' => 'required|numeric|min:0|max:100',
            'details' => 'required|array',
            'details.*.category' => 'required|string',
            'details.*.value' => 'required|numeric|min:0|max:100',
            'store_id' => 'required|exists:stores,id',
            'representative_id' => 'required|exists:representatives,id',
            'country_id' => 'required|exists:countries,id',
            'terms' => 'nullable|array',
            'terms.*' => 'nullable|string',
            'how_it_works' => 'nullable|array',
            'how_it_works.*' => 'nullable|string',
            'verification_period' => 'nullable|integer|min:0',
            'tips' => 'nullable|array',
            'tips.*' => 'nullable|string',
            'launch_date' => 'required|date',
            'expiration_date' => 'nullable|date|after:launch_date',
            'active' => 'nullable|boolean',
        ]);

        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
