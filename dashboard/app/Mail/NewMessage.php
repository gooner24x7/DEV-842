<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMessage extends Mailable
{
    use Queueable, SerializesModels;
    
    private User $user;
    private Message $newMessage;

    public function __construct(User $user, Message $message)
    {
        $this->user = $user;
        $this->newMessage = $message;
    }

    public function build(): self
    {
        return $this->view('mail.newmessage')
            ->with([
                'user' => $this->user,
                'newMessage' => $this->newMessage,
            ])
            ->subject('New message');
    }
}
