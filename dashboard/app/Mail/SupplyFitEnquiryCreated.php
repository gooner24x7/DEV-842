<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Questionnaire\Session;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplyFitEnquiryCreated extends Mailable
{
    use Queueable, SerializesModels;

    private SupplyFitEnquiry $enquiry;
    private User $user;
    private ?Session $session;

    public function __construct(SupplyFitEnquiry $enquiry, User $user, Session $questionnaire = null)
    {
        $this->enquiry = $enquiry;
        $this->user = $user;
        $this->session = $questionnaire;
    }

    public function build(): self
    {
        return $this->view('mail.new_supply_fit_enquiry')->with([
            'user' => $this->user,
            'enquiry' => $this->enquiry,
            'questionnaire' => $this->session,
        ])->subject("New Marketplace Enquiry");
    }
}
