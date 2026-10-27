<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Client;
use App\Models\Country;
use App\Models\Offer;
use App\Models\Representative;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class OfferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.offers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.offers.create', [
            'clients' => Client::query(),
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

        $offer = Offer::create($validated);
        $offer->countries()->attach($validated['countries']);

        return $this->created(__('Offer'), 'dashboard.offers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Offer $offer)
    {
        return view('dashboard.offers.edit', [
            'offer' => $offer,
            'clients' => Client::query(),
            'countries' => Country::query(),
            'representatives' => Representative::query(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Offer $offer)
    {
        $validated = $this->validated($request, $offer);

        $offer->update($validated);
        $offer->countries()->sync($validated['countries']);

        return $this->updated(__('Offer'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Offer $offer)
    {
        $offer->delete();

        return $this->deleted(__('Offer'));
    }

    /**
     * Validate the request for storing/updating an offer.
     */
    protected function validated(Request $request, ?Offer $offer = null): array
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.*' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:offers,code,' . $offer?->id,
            'client_id' => 'required|exists:clients,id',
            'representative_id' => 'required|exists:representatives,id',
            'countries' => 'required|array',
            'countries.*' => 'exists:countries,id',
            'description' => 'nullable|array',
            'description.*' => 'nullable|string',
            'tips' => 'nullable|array',
            'tips.*' => 'nullable|string',
            'fixed_discount' => 'nullable|boolean',
            'discount' => 'required|numeric|min:0|max:100',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'minimum_usages' => 'nullable|integer|min:0',
            'maximum_usages' => 'nullable|integer|min:0',
            'launch_date' => 'required|date',
            'expiration_date' => 'nullable|date|after:launch_date',
            'active' => 'nullable|boolean',
        ]);

        $validated['fixed_discount'] = $request->boolean('fixed_discount');
        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
