<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\SupplyFitEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplyFitEnquiryDuplicated extends Mailable
{
    use Queueable, SerializesModels;

    private SupplyFitEnquiry $duplicated;
    private SupplyFitEnquiry $original;
    private User $user;

    public function __construct(SupplyFitEnquiry $original, SupplyFitEnquiry $duplicated, User $user)
    {
        $this->original = $original;
        $this->duplicated = $duplicated;
        $this->user = $user;
    }

    public function build(): self
    {
        return $this->view('mail.duplicated_supply_fit_enquiry')
            ->with([
                'original' => $this->original,
                'duplicated' => $this->duplicated,
                'user' => $this->user
            ])
            ->subject('Duplicated enquiry');
    }
}
