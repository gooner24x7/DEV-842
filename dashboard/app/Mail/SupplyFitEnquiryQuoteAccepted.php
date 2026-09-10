<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\SupplyFitEnquiryQuote;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplyFitEnquiryQuoteAccepted extends Mailable
{
    use Queueable, SerializesModels;

    private SupplyFitEnquiryQuote $quote;
    private User $user;

    public function __construct(SupplyFitEnquiryQuote $quote, User $user)
    {
        $this->quote = $quote;
        $this->user = $user;
    }

    public function build(): self
    {
        $url = config('app.url') . "/supply-fit-enquiries/{$this->quote->enquiry_id}/quotes";

        return $this->view('mail.accepted_quote')->with([
            'user' => $this->user,
            'quote' => $this->quote,
            'url' => $url,
        ])->subject("Quote has been accepted");
    }
}
