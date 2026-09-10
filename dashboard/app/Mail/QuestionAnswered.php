<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Answer;
use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuestionAnswered extends Mailable
{
    use Queueable, SerializesModels;

    private Answer $answer;
    private Question $question;

    public function __construct(Question $question, Answer $answer)
    {
        $this->question = $question;
        $this->answer = $answer;
    }

    public function build(): self
    {
        $user = $this->question->users()->first();

        return $this->view('mail.newanswer')
            ->with([
                'user' => $user,
                'answer' => $this->answer,
                'question' => $this->question,
            ])
            ->subject('New answer');
    }
}
