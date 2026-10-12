<?php

namespace Tests\Feature;

use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function completeLogin(array $credentials): void
    {
        $this->post('/login', $credentials)->assertRedirect(route('login.code'));
        $this->assertGuest();
        $code = Mail::sent(LoginCode::class)->last()->code;
        $this->post(route('login.code.verify'), ['code' => $code])->assertRedirect(route('dashboard'));
    }

    private function account(): User
    {
        return User::create(['name' => 'Login Tester', 'email' => 'admin@example.test', 'password' => 'CorrectPassword!', 'role' => 'admin']);
    }

    private function failLogin(string $email = 'admin@example.test'): void
    {
        $this->from('/')->post('/login', ['email' => $email, 'password' => 'wrong'])
            ->assertRedirect('/')
            ->assertSessionHasErrors(['email' => 'Invalid login credentials.']);
        $this->assertGuest();
    }

    public function test_successful_login_regenerates_session_and_logout_ends_access(): void
    {
        $user = $this->account();
        $this->withSession(['marker' => 'preserved']);
        $sessionId = session()->getId();
        $this->completeLogin(['email' => $user->email, 'password' => 'CorrectPassword!']);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->post(route('logout'))->assertRedirect('/');
        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_failed_login_preserves_email_but_never_flashes_password(): void
    {
        $this->account();
        $this->failLogin();
        $this->assertSame('admin@example.test', session()->getOldInput('email'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_five_failures_block_valid_credentials_until_the_cooldown_expires(): void
    {
        $this->account();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->failLogin();
        }
        $credentials = ['email' => 'admin@example.test', 'password' => 'CorrectPassword!'];
        $this->postJson('/login', $credentials)->assertStatus(429)->assertJsonValidationErrors('email');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->completeLogin($credentials);
        $this->assertAuthenticated();
    }

    public function test_email_case_changes_do_not_bypass_the_limit_and_html_gets_a_clear_error(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->failLogin($attempt % 2 ? 'ADMIN@example.test' : 'admin@example.test');
        }
        $this->from('/')->post('/login', ['email' => 'Admin@example.test', 'password' => 'wrong'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many login attempts.', session('errors')->first('email'));
        $this->assertNull(session()->getOldInput('password'));
        $this->get('/')->assertOk()->assertSee('Too many login attempts.');
        $this->failLogin('different@example.test');
    }

    public function test_rotating_email_addresses_is_limited_by_ip(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->failLogin("account{$attempt}@example.test");
        }
        $this->postJson('/login', ['email' => 'another@example.test', 'password' => 'wrong'])->assertStatus(429);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);
        $this->failLogin('another@example.test');
    }

    public function test_successful_login_clears_previous_account_failures(): void
    {
        $this->account();
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->failLogin();
        }
        $this->completeLogin(['email' => 'admin@example.test', 'password' => 'CorrectPassword!']);
        $this->post(route('logout'));
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->failLogin();
        }
    }

    public function test_invalid_input_and_unknown_accounts_do_not_authenticate(): void
    {
        $this->postJson('/login', ['email' => 'invalid', 'password' => ['bad']])
            ->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->failLogin('unknown@example.test');
    }
}
