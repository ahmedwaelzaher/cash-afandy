<?php

namespace App\Actions\Auth;

use App\Enums\NotificationType;
use App\Models\User;
use App\Notifications\WebsiteNotification;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralAttribution
{
    public function normalize(mixed $code): string
    {
        return is_string($code) ? Str::upper(trim($code)) : '';
    }

    public function owner(string $code): ?User
    {
        return preg_match('/^[A-Z0-9]{10}$/D', $code)
            ? User::where('referral_code', $code)->first() : null;
    }

    public function current(Request $request, bool $preferCookie = false): ?array
    {
        if (! setting('referral_program_enabled')) {
            $this->forget($request);

            return null;
        }

        if (! $request->hasSession()) return null;

        $session = $request->session()->get('auth.invitation');
        $cookie = json_decode((string) $request->cookie('referral_invitation', ''), true);
        $context = $preferCookie ? ($cookie ?? $session) : ($session ?? $cookie);

        if (! is_array($context) || ! is_string($context['code'] ?? null) || ($context['expires_at'] ?? 0) <= now()->timestamp) {
            // Let the current page remove an expired URL instead of recapturing it as new.
            if (is_array($context) && is_string($context['code'] ?? null)) {
                $request->attributes->set('expired_referral_code', $context['code']);
            }

            $this->forget($request);
            return null;
        }

        $request->session()->put('auth.invitation', $context);

        return $context;
    }

    public function forget(Request $request): void
    {
        if ($request->hasSession()) $request->session()->forget('auth.invitation');
        $request->cookies->remove('referral_invitation');
        Cookie::queue(Cookie::forget('referral_invitation'));
    }

    public function capture(Request $request, string $code): ?array
    {
        $current = $this->current($request);
        if (! setting('referral_program_enabled')) return null;
        $code = $this->normalize($code);
        if ($code === $request->attributes->get('expired_referral_code')) return $current;
        if ($code === ($current['code'] ?? null) || ! $this->owner($code)) return $current;

        $context = $this->context($code);
        $this->remember($request, $context);

        return $context;
    }

    public function forRegistration(Request $request): ?array
    {
        $code = $request->query('referral_code');

        return is_string($code) ? $this->capture($request, $code) : $this->current($request, preferCookie: true);
    }

    public function browserState(Request $request): array
    {
        if (auth('users')->check()) {
            $this->forget($request);

            return ['context' => null, 'proof' => ''];
        }
        $context = $this->current($request);

        return ['context' => $context ? ['code' => $context['code'], 'expires_at' => $context['expires_at']] : null,
            'expired_code' => $request->attributes->get('expired_referral_code'),
            'proof' => $context ? encrypt($context) : ''];
    }

    public function resolve(Request $request, array $input, bool $api = false): ?array
    {
        if (! setting('referral_program_enabled')) return null;
        if (! array_key_exists('referral_code', $input)) return $api ? null : $this->current($request);
        $code = $this->normalize($input['referral_code']);
        if ($code === '') return null;

        // An untouched automatic value must expire even in a form left open for an hour.
        if (! $api && filled($input['referral_context'] ?? null)) {
            try {
                $original = decrypt($input['referral_context']);
            } catch (DecryptException) {
                throw ValidationException::withMessages(['referral_code' => __('Please reopen the registration page and try again.')]);
            }
            if (is_array($original) && $code === ($original['code'] ?? null)) return $this->current($request);
        }
        if (! $this->owner($code)) {
            throw ValidationException::withMessages(['referral_code' => __('The invitation code is invalid.')]);
        }

        return $this->context($code, manual: true);
    }

    public function remember(Request $request, ?array $context): void
    {
        if (! $context) {
            $this->forget($request);

            return;
        }
        $request->session()->put('auth.invitation', $context);
        // Laravel encrypts this HttpOnly cookie; the embedded deadline remains authoritative.
        Cookie::queue(Cookie::make('referral_invitation', json_encode($context),
            max(1, (int) ceil(($context['expires_at'] - now()->timestamp) / 60)), '/', null,
            config('session.secure'), true, false, 'lax'));
    }

    public function attach(User $user, ?array $context): void
    {
        if (! setting('referral_program_enabled') || ! $context || $context['expires_at'] <= now()->timestamp) return;
        $owner = User::where('referral_code', $context['code'])->lockForUpdate()->first();
        if (! $owner || $owner->is($user)) {
            if ($context['manual'] ?? false) {
                throw ValidationException::withMessages(['referral_code' => __('The invitation code is invalid.')]);
            }

            return;
        }
        $referral = $user->referral()->create(['referrer_id' => $owner->id, 'registered_at' => now()]);
        if (! $owner->receivesWebsiteNotification(NotificationType::NewReferral)) return;

        $locale = $owner->preferences?->language;
        if (! in_array($locale, setting('website_locales'), true)) $locale = config('app.fallback_locale');
        $remaining = $owner->referralProgress()['remaining'];
        $name = Str::limit(trim($user->first_name . ' ' . $user->last_name), 100);
        $body = $remaining > 0
            ? __(':name registered using your invitation code. Keep going! Invitations remaining for your reward: :remaining.', ['name' => $name, 'remaining' => $remaining], $locale)
            : __(':name registered using your invitation code. Your invitation goal is now complete!', ['name' => $name], $locale);

        // WebsiteNotification already queues delivery after the registration transaction commits.
        $owner->notify((new WebsiteNotification(
            title: __('A friend joined through your invitation', [], $locale),
            url: route('website.profile.edit', ['locale' => $locale, 'section' => 'referrals']),
            data: ['referral_id' => $referral->id],
            body: $body,
            type: NotificationType::NewReferral,
        ))->locale($locale));
    }

    protected function context(string $code, bool $manual = false): array
    {
        return ['code' => $code, 'expires_at' => now()->addHour()->timestamp, 'manual' => $manual];
    }
}
