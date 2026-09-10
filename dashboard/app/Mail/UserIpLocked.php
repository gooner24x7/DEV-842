<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserIpLocked extends Mailable
{
    use Queueable, SerializesModels;

    private string $user_name;
    private ?string $user_ip;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $username, ?string $ip)
    {
        $this->user_name = $username;
        $this->user_ip = $ip;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('mail.user_ip_locked')->with([
            'user_name' => $this->user_name,
            'user_ip' => $this->user_ip ?? '',
            'domain' => request()->getHost()
        ])->subject("User IP Locked");
    }
}
