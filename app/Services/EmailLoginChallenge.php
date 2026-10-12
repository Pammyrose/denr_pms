<?php

namespace App\Services;

use App\Jobs\SendLoginCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailLoginChallenge
{
    public function pending(Request $request): ?array
    {
        $id = $request->session()->get('email_login_challenge');

        return $id ? Cache::get('email-login:'.$id) : null;
    }

    public function cancel(Request $request): void
    {
        if ($id = $request->session()->pull('email_login_challenge')) {
            Cache::forget('email-login:'.$id);
            Cache::forget('email-login:delivery:'.$id);
        }
    }

    public function deliveryStatus(Request $request): string
    {
        if (! $this->pending($request)) {
            return 'expired';
        }

        return Cache::get('email-login:delivery:'.$request->session()->get('email_login_challenge'), 'queued');
    }

    public function issue(Request $request, User $user): void
    {
        $keys = ['email-login:send:'.$user->id => [1, 60], 'email-login:hour:'.$user->id => [10, 3600]];
        foreach ($keys as $key => [$limit, $seconds]) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw ValidationException::withMessages(['email' => 'Please wait '.RateLimiter::availableIn($key).' seconds before requesting another code.'])->status(429);
            }
        }
        foreach ($keys as $key => [$limit, $seconds]) {
            RateLimiter::hit($key, $seconds);
        }

        $this->cancel($request);
        $code = (string) random_int(100000, 999999);
        $id = Str::random(64);
        Cache::put('email-login:'.$id, [
            'user_id' => $user->id,
            'email' => $user->email,
            'password_hash' => $user->getAuthPassword(),
            'code_hash' => Hash::make($code),
        ], now()->addMinutes(10));
        Cache::put('email-login:delivery:'.$id, 'queued', now()->addMinutes(10));
        try {
            Bus::dispatch(new SendLoginCode($id, $code));
        } catch (\Throwable $exception) {
            Cache::forget('email-login:'.$id);
            Cache::forget('email-login:delivery:'.$id);
            report($exception);
            throw ValidationException::withMessages(['email' => 'We could not request your sign-in code. Please try signing in again in a minute or contact your administrator.']);
        }

        $request->session()->regenerate();
        $request->session()->put('email_login_challenge', $id);
    }

    public function verify(Request $request, string $code): ?User
    {
        $pending = $this->pending($request);
        if (! $pending) {
            $this->cancel($request);

            return null;
        }
        $key = 'email-login:attempts:'.$pending['user_id'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->cancel($request);
            throw ValidationException::withMessages(['code' => 'Too many incorrect codes. Please wait '.RateLimiter::availableIn($key).' seconds, then sign in again.'])->status(429);
        }
        if (! Hash::check($code, $pending['code_hash'])) {
            RateLimiter::hit($key, 600);
            throw ValidationException::withMessages(['code' => 'The code is incorrect. Please check your email and try again.']);
        }

        $user = User::find($pending['user_id']);
        $this->cancel($request);
        if (! $user || $user->email !== $pending['email'] || $user->getAuthPassword() !== $pending['password_hash']) {
            return null;
        }
        RateLimiter::clear($key);

        return $user;
    }
}
