<?php
declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UsersDidntLogin extends Mailable
{
    use Queueable, SerializesModels;

    private Collection $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('mail.users_didnt_login')->with([
            'users' => $this->users,
        ])->subject("Users: not active");
    }
}
