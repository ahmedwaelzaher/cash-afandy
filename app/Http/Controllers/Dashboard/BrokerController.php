<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\Broker;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class BrokerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('dashboard.brokers.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dashboard.brokers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30|starts_with:+|phone',
            'url' => 'nullable|url',
        ]);

        Broker::create($validated);

        return $this->created(__('Broker'), 'dashboard.brokers.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Broker $broker)
    {
        return view('dashboard.brokers.edit', [
            'broker' => $broker,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Broker $broker)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30|starts_with:+|phone',
            'url' => 'nullable|url',
        ]);

        $broker->update($validated);

        return $this->updated(__('Broker'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Broker $broker)
    {
        $broker->delete();

        return $this->deleted(__('Broker'));
    }
}
