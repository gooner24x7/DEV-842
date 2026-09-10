<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\SupplyFitEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplyFitEnquiryCreatedBatch extends Mailable
{
    use Queueable, SerializesModels;

    private array $enquiries;
    private User $user;

    public function __construct(array $enquiries, User $user)
    {
        $this->enquiries = $enquiries;
        $this->user = $user;
    }

    public function build(): self
    {
        return $this->view('mail.new_supply_fit_enquiry_batch')->with([
            'user' => $this->user,
            'enquiries' => $this->enquiries,
            'total' => count($this->enquiries),
        ])->subject("New Marketplace Enquiries");
    }
}
