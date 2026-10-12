<?php

namespace Tests\Feature;

use App\Jobs\SendLoginCode;
use App\Mail\LoginCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QueuedEmailAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'database']);
        Mail::fake();
    }

    private function beginLogin(): array
    {
        $user = User::create(['name' => 'Queue Tester', 'email' => 'queue@example.test', 'password' => 'CorrectPassword!', 'role' => 'admin']);
        $this->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword!'])->assertRedirect(route('login.code'));
        $record = DB::table('jobs')->where('queue', 'email-auth')->first();
        $payload = json_decode($record->payload, true);
        $job = unserialize(Crypt::decrypt($payload['data']['command']));
        $this->assertInstanceOf(SendLoginCode::class, $job);

        return [$user, $job];
    }

    public function test_login_opens_verification_without_sending_mail_and_worker_delivers_encrypted_job(): void
    {
        [$user, $job] = $this->beginLogin();
        Mail::assertNothingSent();
        $this->assertGuest();
        $this->get(route('login.code'))->assertOk()->assertSee('Your code email is on its way');
        $this->getJson(route('login.code.status'))->assertExactJson(['status' => 'queued']);
        $this->artisan('queue:work', ['--queue' => 'email-auth', '--once' => true])->assertExitCode(0);
        Mail::assertSent(LoginCode::class, fn ($mail) => $mail->hasTo($user->email) && $mail->code === $job->code);
        $this->assertDatabaseCount('jobs', 0);
        $this->getJson(route('login.code.status'))->assertExactJson(['status' => 'sent']);
        $this->post(route('login.code.verify'), ['code' => $job->code])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_cancelled_and_expired_challenges_are_not_sent(): void
    {
        [, $job] = $this->beginLogin();
        $this->post(route('logout'));
        $job->handle();
        Mail::assertNothingSent();
        $this->travel(61)->seconds();
        $this->post('/login', ['email' => 'queue@example.test', 'password' => 'CorrectPassword!']);
        $record = DB::table('jobs')->orderByDesc('id')->first();
        $job = unserialize(Crypt::decrypt(json_decode($record->payload, true)['data']['command']));
        $this->travel(601)->seconds();
        $job->handle();
        Mail::assertNothingSent();
        $this->getJson(route('login.code.status'))->assertExactJson(['status' => 'expired']);
    }

    public function test_resending_skips_the_old_email_job(): void
    {
        [, $oldJob] = $this->beginLogin();
        $this->travel(61)->seconds();
        $this->post(route('login.code.resend'))->assertRedirect(route('login.code'));
        $oldJob->handle();
        Mail::assertNothingSent();
        $this->artisan('queue:work', ['--queue' => 'email-auth', '--stop-when-empty' => true])->assertExitCode(0);
        Mail::assertSent(LoginCode::class, 1);
    }

    public function test_account_changes_prevent_delivery_and_show_failure(): void
    {
        [$user, $job] = $this->beginLogin();
        $user->update(['email' => 'changed@example.test']);
        $job->handle();
        Mail::assertNothingSent();
        $this->getJson(route('login.code.status'))->assertExactJson(['status' => 'failed']);
        $this->get(route('login.code'))->assertSee("We couldn't send your code.", false);
    }

    public function test_exhausted_delivery_failure_leaves_user_signed_out_with_retry_message(): void
    {
        [, $job] = $this->beginLogin();
        $job->failed(new \RuntimeException('SMTP unavailable'));
        $this->getJson(route('login.code.status'))->assertExactJson(['status' => 'failed']);
        $this->get(route('login.code'))->assertSee('Request another code after one minute');
        $this->assertGuest();
    }

    public function test_queue_submission_failure_removes_challenge(): void
    {
        $user = User::create(['name' => 'Queue Tester', 'email' => 'queue@example.test', 'password' => 'CorrectPassword!', 'role' => 'admin']);
        Bus::shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        $this->from('/')->post('/login', ['email' => $user->email, 'password' => 'CorrectPassword!'])
            ->assertRedirect('/')->assertSessionHasErrors('email');
        $this->assertNull(session('email_login_challenge'));
        $this->assertDatabaseCount('jobs', 0);
        Mail::assertNothingSent();
        $this->assertGuest();
    }
}
