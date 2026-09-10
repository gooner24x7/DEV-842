<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PreferredUploaded extends Mailable
{
    use Queueable, SerializesModels;

    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build(): self
    {
        return $this->view('mail.pref_users_upload')
            ->with([
                'user' => $this->user,
                'preferredUsersFileUrl' => $this->user->getPreferredUsersFileUrl(),
            ])
            ->subject('Preferred user file has been uploaded');
    }
}
