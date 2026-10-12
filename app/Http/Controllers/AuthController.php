<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailLoginChallenge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private EmailLoginChallenge $challenge) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $accountKey = 'login:account:'.hash('sha256', Str::lower(trim($credentials['email'])).'|'.$request->ip());
        $ipKey = 'login:ip:'.hash('sha256', (string) $request->ip());
        $retryAfter = 0;

        foreach ([$accountKey => 5, $ipKey => 30] as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $retryAfter = max($retryAfter, RateLimiter::availableIn($key));
            }
        }

        if ($retryAfter > 0) {
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$retryAfter} seconds.",
            ])->status(429);
        }

        if (Auth::validate($credentials)) {
            $user = User::where('email', $credentials['email'])->firstOrFail();
            $this->challenge->issue($request, $user);
            RateLimiter::clear($accountKey);

            return redirect()->route('login.code');
        }

        RateLimiter::hit($accountKey, 60);
        RateLimiter::hit($ipKey, 60);
        $this->challenge->cancel($request);

        return back()->withErrors([
            'email' => 'Invalid login credentials.',
        ])->withInput($request->only('email'));
    }

    public function showCode(Request $request)
    {
        if (! $this->challenge->pending($request)) {
            return redirect()->route('login')->with('error', 'Your sign-in code expired. Please sign in again.');
        }

        return view('auth.verify-code', ['deliveryStatus' => $this->challenge->deliveryStatus($request)]);
    }

    public function codeStatus(Request $request)
    {
        return response()->json(['status' => $this->challenge->deliveryStatus($request)]);
    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        $user = $this->challenge->verify($request, $validated['code']);
        if (! $user) {
            return redirect()->route('login')->with('error', 'Your sign-in code expired or your account changed. Please sign in again.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function resendCode(Request $request)
    {
        $pending = $this->challenge->pending($request);
        $user = $pending ? User::find($pending['user_id']) : null;
        if (! $user || $user->email !== $pending['email'] || $user->getAuthPassword() !== $pending['password_hash']) {
            $this->challenge->cancel($request);

            return redirect()->route('login')->with('error', 'Please sign in again to request a new code.');
        }

        $this->challenge->issue($request, $user);

        return redirect()->route('login.code')->with('status', 'A new code has been requested. Use the latest code from your email.');
    }
}
