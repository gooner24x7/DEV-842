<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\LogisticsEnquiry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LogisticsEnquiryCreated extends Mailable
{
    use Queueable, SerializesModels;

    private LogisticsEnquiry $enquiry;
    private User $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(LogisticsEnquiry $enquiry, User $user)
    {
        $this->enquiry = $enquiry;
        $this->user = $user;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('mail.new_logistics_enquiry')->with([
            'user' => $this->user,
            'enquiry' => $this->enquiry,
        ])->subject("New logistics enquiry");
    }
}
