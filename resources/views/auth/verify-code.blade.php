@extends('auth.layout', ['verification' => true])

@section('title', 'Verify sign-in')

@section('content')
    <div class="code-icon"><i class="fa-regular fa-envelope-open" aria-hidden="true"></i></div>
    <h2>Check your email</h2>
    <p class="intro">Enter the six-digit code from your account's email to finish signing in.</p>
    <div class="alert {{ ($deliveryStatus ?? 'queued') === 'failed' ? '' : 'alert-success' }}" role="status" aria-live="polite" data-email-delivery="{{ $deliveryStatus ?? 'queued' }}" data-status-url="{{ route('login.code.status') }}">
        @if (($deliveryStatus ?? 'queued') === 'sent')
            Your code email has been sent. Check your inbox or spam folder.
        @elseif (($deliveryStatus ?? 'queued') === 'failed')
            We couldn't send your code. Request another code after one minute, or contact your administrator.
        @else
            Your code email is on its way. Delivery can take a few seconds.
        @endif
    </div>
    @if (session('status'))
        <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('login.code.verify') }}" method="POST" data-submit-loading>
        @csrf
        <div class="field">
            <label for="code">Sign-in code</label>
            <input id="code" name="code" type="text" class="code-input" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus aria-describedby="code-help" @error('code') aria-invalid="true" @enderror>
            <p id="code-help" class="field-help">Expires in 10 minutes. Use your latest code.</p>
        </div>
        <button class="button button-primary" type="submit" data-loading-label="Verifying…">
            <span data-submit-label>Verify and sign in</span>
            <i class="fa-solid fa-arrow-right" data-submit-icon aria-hidden="true"></i>
            <span class="button-spinner" data-submit-spinner aria-hidden="true" hidden></span>
        </button>
    </form>
    <form action="{{ route('login.code.resend') }}" method="POST" class="resend-form">
        @csrf
        <button class="button button-secondary" type="submit">Send another code</button>
    </form>
    <p class="field-help">Check your spam folder. Resend available once per minute.</p>
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button class="cancel-button" type="submit"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Return to sign in</button>
    </form>
@endsection
