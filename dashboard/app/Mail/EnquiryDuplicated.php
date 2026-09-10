<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EnquiryDuplicated extends Mailable
{
    use Queueable, SerializesModels;

    private Question $duplicated;
    private Question $original;
    private User $user;

    public function __construct(Question $original, Question $duplicated, User $user)
    {
        $this->original = $original;
        $this->duplicated = $duplicated;
        $this->user = $user;
    }

    public function build(): self
    {
        return $this->view('mail.duplicated_enquiry')
            ->with([
                'original' => $this->original,
                'duplicated' => $this->duplicated,
                'user' => $this->user
            ])
            ->subject('Duplicated enquiry');
    }
}
