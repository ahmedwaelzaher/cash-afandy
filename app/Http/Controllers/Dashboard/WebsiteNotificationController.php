<?php

namespace App\Http\Controllers\Dashboard;

use App\Jobs\SendScheduledWebsiteNotifications;
use App\Models\Country;
use App\Models\ScheduledWebsiteNotification;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Redot\Http\Controllers\Controller;
use Redot\Support\QueryFilters;

class WebsiteNotificationController extends Controller
{
    public function index()
    {
        return view('dashboard.website-notifications.index');
    }

    public function create()
    {
        return view('dashboard.website-notifications.create', ['users' => User::query()]);
    }

    public function recipients(Request $request)
    {
        $users = $this->recipientQuery($request)->orderBy('id')->get(['id', 'full_name', 'email']);

        return response()->json($users->map(fn (User $user) => [
            '__value' => $user->id,
            '__text' => $user->full_name . ' (' . $user->email . ')',
        ]));
    }

    protected function recipientQuery(Request $request): Builder
    {
        $validated = $request->validate(['filters' => ['required', 'json', 'max:20000']]);
        $rules = json_decode($validated['filters'], true);
        $definitions = collect(QueryFilters::resolve(User::class))->keyBy('id');
        $count = 0;

        // Only fields and operators exposed by this user's selector may be replayed.
        $valid = function ($group, int $depth = 0) use (&$valid, &$count, $definitions): bool {
            if (! is_array($group) || $depth > 2 || ! in_array($group['condition'] ?? null, ['AND', 'OR'], true)
                || ! is_array($group['rules'] ?? null) || ! array_is_list($group['rules']) || $group['rules'] === []) {
                return false;
            }

            foreach ($group['rules'] as $rule) {
                if (! is_array($rule) || ++$count > 50) return false;
                if (isset($rule['rules'])) {
                    if (! $valid($rule, $depth + 1)) return false;

                    continue;
                }

                if (! is_string($rule['id'] ?? null)) return false;
                $definition = $definitions->get($rule['id']);
                if (! $definition || ! is_string($rule['field'] ?? null)
                    || ! in_array($rule['operator'] ?? null, $definition['operators'], true)) return false;

                try {
                    if (decrypt($rule['field']) !== decrypt($definition['field'])) return false;
                } catch (DecryptException) {
                    return false;
                }

                $value = $rule['value'] ?? null;
                $operator = $rule['operator'];
                if (in_array($operator, ['between', 'not_between'], true)) {
                    if (! is_array($value) || count($value) !== 2 || ! array_is_list($value)) return false;
                } elseif (! in_array($operator, ['in', 'not_in'], true) && is_array($value)) {
                    return false;
                }
                foreach (is_array($value) ? $value : [$value] as $item) {
                    if (! is_scalar($item) && $item !== null) return false;
                }
            }

            return true;
        };

        if (! $valid($rules)) {
            throw ValidationException::withMessages(['filters' => __('validation.in', ['attribute' => __('Filter')])]);
        }

        return QueryFilters::query($rules, User::query());
    }

    public function store(Request $request)
    {
        $request->mergeIfMissing(['recipient_mode' => 'custom']);
        // A single JSON field preserves large selections beyond PHP's max_input_vars.
        if (is_string($request->input('users'))) {
            $request->merge(['users' => json_decode($request->input('users'), true)]);
        }

        $validated = $request->validate([
            'recipient_mode' => ['required', Rule::in(['custom', 'condition'])],
            'users' => ['exclude_unless:recipient_mode,custom', 'required', 'array', 'min:1'],
            'users.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:500'],
            'url' => ['nullable', 'url:http,https', 'max:2048'],
            'send_at' => ['required', 'date_format:Y-m-d H:i,Y-m-d H:i:s,Y-m-d h:i A'],
        ]);

        // Delayed notifications must use a persistent queue, never the sync driver.
        if (! in_array(config('queue.connections.' . config('queue.default') . '.driver'), ['database', 'redis', 'beanstalkd'], true)) {
            throw ValidationException::withMessages(['send_at' => __('validation.in', ['attribute' => __('Date')])]);
        }

        if ($validated['recipient_mode'] === 'condition') {
            abort_unless(route_allowed('dashboard.website-notifications.recipients'), 403);
            $recipients = $this->recipientQuery($request);
        } else {
            $recipients = User::query()->whereKey($validated['users']);
        }

        $users = $recipients->with(['preferences.country', 'pushSubscriptions.country'])->get();
        $recipientIds = $users->modelKeys();
        if ($recipientIds === []) {
            $field = $validated['recipient_mode'] === 'condition' ? 'filters' : 'users';
            throw ValidationException::withMessages([$field => __('No users found')]);
        }

        $groups = [];
        $defaultCountry = Country::code(setting('default_website_country'));
        foreach ($users as $user) {
            $timezone = $user->notificationTimezone($defaultCountry);
            if (! $timezone) {
                throw ValidationException::withMessages(['send_at' => __('The recipient country must have a supported timezone.')]);
            }
            $local = Carbon::parse($validated['send_at'], $timezone);
            $groups[$timezone] ??= ['timezone' => $timezone, 'send_at' => $local->copy()->setTimezone(config('app.timezone')), 'recipient_ids' => []];
            $groups[$timezone]['recipient_ids'][] = $user->id;
        }

        DB::transaction(function () use ($validated, $recipientIds, $groups) {
            $entry = ScheduledWebsiteNotification::create([
                'title' => $validated['title'],
                'body' => $validated['body'],
                'url' => $validated['url'] ?? null,
                'recipient_ids' => $recipientIds,
                // Display the requested wall clock time; delivery rows hold the actual sending instants.
                'send_at' => Carbon::parse($validated['send_at'], config('app.timezone')),
            ]);
            $entry->deliveries()->createMany(array_values($groups));
            if ($entry->deliveries()->where('send_at', '<=', now())->exists()) {
                SendScheduledWebsiteNotifications::dispatch($entry->id)->afterCommit();
            }
        });

        return $this->created(__('Notification'), 'dashboard.website-notifications.index');
    }

    public function destroy(string $websiteNotification)
    {
        DB::transaction(function () use ($websiteNotification) {
            $entry = ScheduledWebsiteNotification::query()->pending()
                ->whereKey($websiteNotification)->lockForUpdate()->firstOrFail();
            $entry->delete();
        });

        return $this->deleted(__('Notification'));
    }
}
