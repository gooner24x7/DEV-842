<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\SupplyFitEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NoQuestionnaireResponseForEnquiry extends Mailable
{
    use Queueable, SerializesModels;

    private SupplyFitEnquiry $enquiry;
    private User $user;
    private User $company;
    private string $hash;

    public function __construct(SupplyFitEnquiry $enquiry, User $user, User $company, string $hash)
    {
        $this->enquiry = $enquiry;
        $this->user = $user;
        $this->company = $company;
        $this->hash = $hash;
    }

    public function build(): self
    {
        return $this->view('mail.no_questionnaire_response_for_enquiry')->with([
            'user' => $this->user,
            'enquiry' => $this->enquiry,
            'company' => $this->company,
            'hash' => $this->hash,
        ])->subject("Questionnaire For Marketplace enquiry #" . $this->enquiry->id);
    }
}
