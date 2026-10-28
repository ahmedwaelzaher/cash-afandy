<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Broker;
use App\Models\Country;
use App\Models\Representative;
use App\Models\Store;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class RepresentativeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Broker $broker)
    {
        return view('dashboard.brokers.representatives.index', [
            'broker' => $broker,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Broker $broker)
    {
        return view('dashboard.brokers.representatives.create', [
            'broker' => $broker,
            'stores' => Store::query(),
            'countries' => Country::query(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Broker $broker)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30|starts_with:+|phone',
            'stores' => 'nullable|array',
            'stores.*.store_id' => 'required|exists:stores,id',
            'stores.*.country_id' => 'required|exists:countries,id',
        ]);

        $broker->representatives()->create($validated);

        return $this->created(__('Representative'), 'dashboard.brokers.representatives.index', ['broker' => $broker]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Broker $broker, Representative $representative)
    {
        return view('dashboard.brokers.representatives.edit', [
            'broker' => $broker,
            'representative' => $representative,
            'stores' => Store::query(),
            'countries' => Country::query(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Broker $broker, Representative $representative)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30|starts_with:+|phone',
            'stores' => 'nullable|array',
            'stores.*.store_id' => 'required|exists:stores,id',
            'stores.*.country_id' => 'required|exists:countries,id',
        ]);

        $representative->update($validated);

        return $this->updated(__('Representative'), 'dashboard.brokers.representatives.index', ['broker' => $broker]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Broker $broker, Representative $representative)
    {
        $representative->delete();

        return $this->deleted(__('Representative'), 'dashboard.brokers.representatives.index', ['broker' => $broker]);
    }
}
