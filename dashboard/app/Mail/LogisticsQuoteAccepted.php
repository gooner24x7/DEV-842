<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\LogisticsQuote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LogisticsQuoteAccepted extends Mailable
{
    use Queueable, SerializesModels;

    private LogisticsQuote $quote;
    private User $user;

    public function __construct(LogisticsQuote $quote, User $user)
    {
        $this->quote = $quote;
        $this->user = $user;
    }

    public function build(): self
    {
        $url = config('app.url') . "/logistics-quotes/{$this->quote->enquiry_id}";

        return $this->view('mail.accepted_quote')->with([
            'user' => $this->user,
            'quote' => $this->quote,
            'url' => $url,
        ])->subject("Quote has been accepted");
    }
}
