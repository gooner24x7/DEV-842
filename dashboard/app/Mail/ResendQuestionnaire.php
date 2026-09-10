<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Questionnaire\Session;
use App\Models\SupplyFitEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResendQuestionnaire extends Mailable
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
        return $this->view('mail.resend_questionnaire_for_enquiry')->with([
            'user' => $this->user,
            'enquiry' => $this->enquiry,
            'questionnaire' => $this->session,
        ])->subject("Questionnaire For Marketplace enquiry #" . $this->enquiry->id);
    }
}
