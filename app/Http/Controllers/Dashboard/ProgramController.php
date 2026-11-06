<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ProgramType;
use App\Models\Country;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Redot\Http\Controllers\Controller;

class ProgramController extends Controller
{
    /**
     * Display program management.
     */
    public function index()
    {
        return view('dashboard.programs.index');
    }

    /**
     * Show the program creation form.
     */
    public function create()
    {
        return view('dashboard.programs.create', ['countries' => Country::forAccounts()]);
    }

    /**
     * Store a program with an exact monetary amount.
     */
    public function store(Request $request)
    {
        Program::create($this->validated($request));

        return $this->created(__('Afandy programs'), 'dashboard.programs.index');
    }

    /**
     * Display subscribers and their earned reward cycles.
     */
    public function show(Program $program)
    {
        return view('dashboard.programs.show', [
            'program' => $program->load('country'),
            'subscriptions' => $program->subscriptions()->with(['user', 'reward'])->latest('id')->paginate(20),
        ]);
    }

    /**
     * Show the program editing form.
     */
    public function edit(Program $program)
    {
        return view('dashboard.programs.edit', ['program' => $program, 'countries' => Country::forAccounts()]);
    }

    /**
     * Update content while retaining the conditions accepted by existing subscribers.
     */
    public function update(Request $request, Program $program)
    {
        $validated = $this->validated($request, $program);

        DB::transaction(function () use ($program, $validated) {
            // Serialize definition changes with enrollment and reward calculation.
            $program = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $program->fill($validated);
            if ($program->subscriptions()->exists() && $program->isDirty([
                'country_id', 'currency', 'type',
                'repeatable', 'max_subscriptions', 'requires_approval', 'launched_at', 'expired_at',
            ])) {
                throw ValidationException::withMessages(['program' => __('Eligibility settings cannot change after the first subscription. Create a new program instead.')]);
            }

            $program->save();
        }, 3);

        return $this->updated(__('Afandy programs'));
    }

    /**
     * Validate translated content, supported conditions and reward settings.
     */
    protected function validated(Request $request, ?Program $program = null): array
    {
        // Match the existing preference form's native checkbox normalization.
        foreach (['repeatable', 'requires_approval', 'active'] as $field) {
            if ($request->input($field) === 'on') $request->merge([$field => 1]);
        }

        // Use the existing wallet amount format and translation input conventions.
        $required = $program ? 'sometimes' : 'required';
        $repeatable = $request->has('repeatable') ? $request->boolean('repeatable') : ($program?->repeatable ?? false);
        $expirationRequired = ! $program || $request->has('repeatable') ? 'required' : 'sometimes';
        $validated = $request->validate([
            'title' => ['required', 'array', 'min:1'],
            'title.*' => ['required', 'string', 'max:255'],
            'description' => ['required', 'array', 'min:1'],
            'description.*' => ['required', 'string'],
            'terms' => ['required', 'array', 'min:1'],
            'terms.*' => ['required', 'string'],
            'country_id' => [$required, 'integer', Rule::in(Country::forAccounts()->pluck('id')->all())],
            'type' => [$required, Rule::enum(ProgramType::class)],
            'reward_amount' => [$required, 'string', 'numeric', 'min:0.01', 'regex:/\A(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,2})?\z/'],
            'repeatable' => ['sometimes', 'boolean'],
            'max_subscriptions' => [$required, 'integer', 'min:1', 'max:1000000'],
            'requires_approval' => ['sometimes', 'boolean'],
            'active' => ['nullable', 'boolean'],
            'launched_at' => [$required, 'date'],
            'expired_at' => [Rule::excludeIf($repeatable), $expirationRequired, 'date', 'after:' . ($request->input('launched_at') ?? $program?->launched_at?->toDateTimeString())],
        ]);

        // Snapshot the currency and convert to minor units without floating point.
        if (isset($validated['reward_amount'])) {
            [$whole, $fraction] = array_pad(explode('.', $validated['reward_amount']), 2, '');
            $validated['reward_amount'] = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        }
        if (isset($validated['country_id'])) $validated['currency'] = Country::findOrFail($validated['country_id'])->currency;
        if ($repeatable) $validated['expired_at'] = null;
        $validated['active'] = $request->boolean('active');

        return $validated;
    }
}
