<?php

namespace App\Http\Controllers\Dashboard;

use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Redot\Http\Controllers\Controller;
use Redot\Support\QueryFilters;

class WebsiteNotificationController extends Controller
{
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
            'send_at' => ['required', 'date', 'after:now'],
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

        if (! $recipients->exists()) {
            $field = $validated['recipient_mode'] === 'condition' ? 'filters' : 'users';
            throw ValidationException::withMessages([$field => __('No users found')]);
        }

        $notification = (new WebsiteNotification(
            title: $validated['title'],
            url: $validated['url'] ?? null,
            body: $validated['body'],
        ))->delay(Carbon::parse($validated['send_at'], config('app.timezone')));

        $recipients->chunkById(100, function ($users) use ($notification) {
            Notification::send($users, $notification);
        });

        return $this->created(__('Notification'), 'dashboard.website-notifications.create');
    }
}
