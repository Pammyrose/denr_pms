<?php

namespace App\Jobs;

use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendLoginCode implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [5, 15];

    public function __construct(public string $challengeId, public string $code)
    {
        $this->onQueue('email-auth');
    }

    public function handle(): void
    {
        $pending = Cache::get('email-login:'.$this->challengeId);
        if (! $pending) {
            return;
        }
        $user = User::find($pending['user_id']);
        if (! $user || $user->email !== $pending['email'] || $user->getAuthPassword() !== $pending['password_hash']) {
            Cache::put('email-login:delivery:'.$this->challengeId, 'failed', now()->addMinutes(10));

            return;
        }

        Mail::to($user->email)->send(new LoginCode($this->code));
        Cache::put('email-login:delivery:'.$this->challengeId, 'sent', now()->addMinutes(10));
    }

    public function failed(?Throwable $exception): void
    {
        if (Cache::has('email-login:'.$this->challengeId)) {
            Cache::put('email-login:delivery:'.$this->challengeId, 'failed', now()->addMinutes(10));
        }
    }
}
