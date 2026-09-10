<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuestionCreated extends Mailable
{
    use Queueable, SerializesModels;

    private Question $question;
    private User $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Question $question, User $user)
    {
        $this->question = $question;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('mail.newquestion')->with([
            'user' => $this->user,
            'question' => $this->question,
        ])->subject("New question");
    }
}
