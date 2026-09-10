<?php
declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MicrosoftUserNotFound extends Mailable
{
    use Queueable, SerializesModels;

    private string $email;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $email)
    {
        $this->email = $email;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('mail.ms_user_not_found')->with([
            'email' => $this->email,
        ])->subject("Microsoft user not found");
    }
}
