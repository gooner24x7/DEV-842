<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NudgeSales extends Mailable
{
    use Queueable, SerializesModels;
    
    private Question $inquiry;

    public function __construct(Question $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    public function build(): self
    {
        return $this->view('mail.nudgesales')
            ->with([
                'inquiry' => $this->inquiry,
            ])
            ->subject('Merchants needed for ID: ' . $this->inquiry->id);
    }
}
