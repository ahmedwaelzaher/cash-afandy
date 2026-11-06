<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\Programs\RecieveProgramReward;
use App\Models\ProgramReward;
use Illuminate\Http\Request;
use Redot\Http\Controllers\Controller;

class ProgramRewardController extends Controller
{
    /**
     * Display program rewards awaiting administrative review.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
        ]);

        return view('dashboard.program-rewards.index', [
            'programId' => isset($validated['program_id']) ? (int) $validated['program_id'] : null,
        ]);
    }

    /**
     * Show the earned subscription reward before review.
     */
    public function show(ProgramReward $programReward)
    {
        return view('dashboard.program-rewards.show', [
            'reward' => $programReward->load(['subscription.program', 'subscription.user', 'admin']),
        ]);
    }

    /**
     * Accept or reject an earned reward through the existing permission boundary.
     */
    public function update(Request $request, ProgramReward $programReward, RecieveProgramReward $receive)
    {
        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'review_note' => ['required_if:approved,0', 'nullable', 'string', 'max:500'],
        ]);

        $receive->handle($programReward, (bool) $validated['approved'], $request->user('admins'), $validated['review_note'] ?? null);

        return $this->updated(__('Program rewards'));
    }
}
