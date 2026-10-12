<?php

namespace Tests\Feature;

use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function beginLogin(): array
    {
        $user = User::create(['name' => 'Tester', 'email' => 'tester@example.test', 'password' => 'CorrectPassword!', 'role' => 'admin']);
        $this->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword!'])->assertRedirect(route('login.code'));
        Mail::assertSent(LoginCode::class, fn ($mail) => $mail->hasTo($user->email));

        return [$user, Mail::sent(LoginCode::class)->last()->code];
    }

    public function test_authentication_pages_do_not_expose_server_secrets_or_cache_content(): void
    {
        config(['mail.mailers.smtp.password' => 'smtp-secret-for-test']);
        [$user, $code] = $this->beginLogin();
        $id = session('email_login_challenge');
        $pending = Cache::get('email-login:'.$id);

        foreach (['login', 'login.code'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $response->assertHeader('Pragma', 'no-cache')
                ->assertDontSee($code)
                ->assertDontSee($id)
                ->assertDontSee($pending['code_hash'])
                ->assertDontSee($user->getAuthPassword())
                ->assertDontSee('smtp-secret-for-test');
        }

        $this->post(route('logout'))->assertRedirect('/')
            ->assertHeader('Pragma', 'no-cache');
    }

    public function test_private_server_files_cannot_be_read_publicly(): void
    {
        foreach (['/.env', '/storage/logs/laravel.log', '/app/Services/EmailLoginChallenge.php'] as $path) {
            $response = $this->get($path);
            $this->assertContains($response->status(), [403, 404], 'Private file must be denied: '.$path);
        }
    }

    public function test_password_alone_does_not_grant_access_and_code_is_single_use(): void
    {
        [$user, $code] = $this->beginLogin();
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('login.code'))->assertOk()->assertSee('Check your email');
        $id = session('email_login_challenge');
        $this->assertNotSame($code, Cache::get('email-login:'.$id)['code_hash']);
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull(Cache::get('email-login:'.$id));
        $this->post(route('logout'));
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_expired_and_missing_challenges_cannot_authenticate(): void
    {
        $this->post(route('login.code.verify'), ['code' => '123456'])->assertRedirect(route('login'));
        [, $code] = $this->beginLogin();
        $this->travel(601)->seconds();
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_wrong_codes_are_limited_even_after_resending(): void
    {
        [$user, $code] = $this->beginLogin();
        $wrong = $code === '111111' ? '222222' : '111111';
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login.code'))->post(route('login.code.verify'), ['code' => $wrong])
                ->assertSessionHasErrors('code');
        }
        $this->assertNull(session()->getOldInput('code'));
        $this->travel(61)->seconds();
        $this->post(route('login.code.resend'))->assertRedirect(route('login.code'));
        $newCode = Mail::sent(LoginCode::class)->last()->code;
        $this->postJson(route('login.code.verify'), ['code' => $newCode])->assertStatus(429);
        $this->assertGuest();
        $this->assertNull(session('email_login_challenge'));
    }

    public function test_resend_has_cooldown_and_invalidates_old_challenge(): void
    {
        [, $code] = $this->beginLogin();
        $oldId = session('email_login_challenge');
        $this->postJson(route('login.code.resend'))->assertStatus(429);
        $this->travel(61)->seconds();
        $this->post(route('login.code.resend'))->assertRedirect(route('login.code'));
        $this->assertNull(Cache::get('email-login:'.$oldId));
        $this->assertNotSame($oldId, session('email_login_challenge'));
        Mail::assertSent(LoginCode::class, 2);
        $this->post(route('login.code.verify'), ['code' => Mail::sent(LoginCode::class)->last()->code])->assertRedirect(route('dashboard'));
    }

    public function test_account_changes_invalidate_pending_login(): void
    {
        [$user, $code] = $this->beginLogin();
        $user->update(['email' => 'changed@example.test']);
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_password_changes_and_deleted_accounts_invalidate_pending_login(): void
    {
        [$user, $code] = $this->beginLogin();
        $user->update(['password' => 'NewPassword!']);
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'NewPassword!']);
        $code = Mail::sent(LoginCode::class)->last()->code;
        $user->delete();
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_delivery_failure_keeps_user_signed_out(): void
    {
        $user = User::create(['name' => 'Tester', 'email' => 'tester@example.test', 'password' => 'CorrectPassword!', 'role' => 'admin']);
        Mail::shouldReceive('to')->once()->with($user->email)->andThrow(new \RuntimeException('Mail unavailable'));
        $this->from('/')->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword!'])
            ->assertRedirect('/')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNull(session('email_login_challenge'));
    }

    public function test_logout_cancels_pending_challenge_and_invalid_password_sends_no_mail(): void
    {
        [$user, $code] = $this->beginLogin();
        $id = session('email_login_challenge');
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertNull(Cache::get('email-login:'.$id));
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        Mail::assertSent(LoginCode::class, 1);
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
