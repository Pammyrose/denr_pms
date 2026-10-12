<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#16366f">
    <title>@yield('title') - DENR-CAR PMS</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}?v={{ filemtime(public_path('css/auth.css')) }}">
    <script src="{{ asset('js/auth.js') }}?v={{ filemtime(public_path('js/auth.js')) }}" defer></script>
</head>
<body class="auth-compact {{ ($verification ?? false) ? 'auth-verification' : 'auth-account' }}">
    <main class="auth-shell">
        <section class="auth-story" aria-label="DENR-CAR Performance Monitoring System">
            <div class="brand">
                <img src="{{ asset('denr_logo.png') }}" alt="DENR logo" width="64" height="64">
                <div><strong>DENR <span>/</span> CAR</strong><p>Department of Environment<br>and Natural Resources</p></div>
            </div>
            <div class="story-copy">
                <p class="eyebrow"><span></span> CORDILLERA ADMINISTRATIVE REGION</p>
                <h1>Turning plans<br>into <em>progress.</em></h1>
                <p class="story-description">A shared space to monitor performance,<br class="desktop-break"> track accomplishments, and move our region forward.</p>
            </div>
            <svg class="landscape" viewBox="0 0 600 390" preserveAspectRatio="xMidYMin slice" aria-hidden="true" focusable="false">
                <defs>
                    <linearGradient id="mountain-light" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#7c9fd9"/><stop offset="1" stop-color="#3a64a7"/></linearGradient>
                    <linearGradient id="mountain-dark" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#244e91"/><stop offset="1" stop-color="#16366f"/></linearGradient>
                </defs>
                <circle cx="437" cy="111" r="49" fill="#d7e7ff" opacity=".9"/>
                <path d="M-40 257 79 162 148 214 290 63 399 185 452 133 638 255V390H-40Z" fill="url(#mountain-light)"/>
                <path d="m290 63-67 118 65-32 59 15Z" fill="#b8ceef" opacity=".5"/>
                <path d="M-30 304 111 233 196 263 320 178 420 265 532 203 650 291V390H-30Z" fill="#426fb1"/>
                <path d="M-30 362 100 271 218 319 342 260 452 314 600 241 650 350V390H-30Z" fill="url(#mountain-dark)"/>
                <path d="M-30 390V341Q110 305 215 359T430 352T630 342V390Z" fill="#102b5c"/>
                <g fill="#102b5c"><path d="M47 321 64 268 81 321H70V350H58V321Z"/><path d="M77 333 101 258 125 333H108V365H94V333Z"/><path d="M464 338 485 271 506 338H492V367H479V338Z"/><path d="M501 334 530 244 559 334H538V370H522V334Z"/></g>
                <path d="M279 390Q359 356 299 330T331 293" fill="none" stroke="#c3d7f5" stroke-width="3" opacity=".35"/>
            </svg>
            <div class="story-footer"><span class="region-mark"><i class="fa-solid fa-leaf" aria-hidden="true"></i> Rooted in purpose.</span><span>RO &middot; PENRO &middot; CENRO</span></div>
        </section>
        <section class="auth-panel" aria-label="Sign in">
            <div class="panel-top"><span class="product-label">PERFORMANCE MONITORING SYSTEM</span></div>
            <div class="auth-content">
                <ol class="sign-in-steps" aria-label="Sign-in progress">
                    <li class="{{ ($verification ?? false) ? 'complete' : 'current' }}" @if (!($verification ?? false)) aria-current="step" @endif><span>01</span> Account</li>
                    <li class="step-line" aria-hidden="true"></li>
                    <li class="{{ ($verification ?? false) ? 'current' : '' }}" @if ($verification ?? false) aria-current="step" @endif><span>02</span> Verify email</li>
                </ol>
                @yield('content')
            </div>
            <div class="panel-footer"><span>&copy; {{ date('Y') }} DENR-CAR</span><span>Working together. Moving forward.</span></div>
        </section>
    </main>
</body>
</html>
