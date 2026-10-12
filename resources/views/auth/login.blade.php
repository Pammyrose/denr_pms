@extends('auth.layout')

@section('title', 'Sign in')

@section('content')
    <h2>Welcome back.</h2>
    <p class="intro">Sign in to your account to continue your work.</p>
    @if (session('error'))
        <div class="alert" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form action="{{ url('/login') }}" method="POST" data-submit-loading>
        @csrf
        <div class="field">
            <label for="email">Email address</label>
            <div class="input-wrap">
                <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')<div id="email-error" class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                <input id="password" type="password" name="password" data-password required autocomplete="current-password" placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false" hidden><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
            </div>
            @error('password')<div id="password-error" class="field-error">{{ $message }}</div>@enderror
        </div>
        <div class="security-note">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            <div><strong>An extra layer of protection</strong><p>We'll email you a code to complete sign-in.</p></div>
        </div>
        <button class="button button-primary" type="submit" data-loading-label="Signing in…">
            <span data-submit-label>Sign in</span>
            <i class="fa-solid fa-arrow-right" data-submit-icon aria-hidden="true"></i>
            <span class="button-spinner" data-submit-spinner aria-hidden="true" hidden></span>
        </button>
    </form>
    <p class="account-help">Need help accessing your account?<br>Contact your system administrator.
        @if (Route::has('password.request'))<br><a href="{{ route('password.request') }}">Forgot password?</a>@endif
    </p>
@endsection
