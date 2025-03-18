<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $token;
    public $role;

    public function __construct($token, $role)
    {
        $this->token = $token;
        $this->role = $role;
    }

    public function build()
    {
        if ($this->role == 'admin' || $this->role == 'manager') {
            return $this->subject('Đặt lại mật khẩu')
                ->view('emails.reset-passwordadmin')
                ->with(['token' => $this->token]);
        }
        return $this->subject('Đặt lại mật khẩu')
            ->view('emails.reset-password')
            ->with(['token' => $this->token]);
    }
}
