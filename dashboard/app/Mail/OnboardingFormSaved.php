<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class OnboardingFormSaved extends Mailable
{
    use Queueable, SerializesModels;

    private User $user;
    private string $app_url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->app_url = Config::get('app.url');
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('mail.onboarding_form_saved')->with([
            'user' => $this->user,
            'app_url' => $this->app_url,
        ])->subject("Onboarding Form Saved");
    }
}
