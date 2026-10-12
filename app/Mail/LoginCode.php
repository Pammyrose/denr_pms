<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LoginCode extends Mailable
{
    public function __construct(public string $code) {}

    public function build(): static
    {
        return $this->subject('Your PMS sign-in code')->view('emails.login-code');
    }
}
