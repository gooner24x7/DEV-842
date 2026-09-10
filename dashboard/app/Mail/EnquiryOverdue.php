<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Question;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

class EnquiryOverdue extends Mailable
{
    use Queueable, SerializesModels;

    private User $user;
    private Question $question;
    private string $token;
    private string $app_url;


    public function __construct(User $user, Question $question, string $token)
    {
        $this->user = $user;
        $this->question = $question;
        $this->token = $token;
        $this->app_url = Config::get('app.url');
    }

    public function build(): self
    {
        return $this->view('mail.enquiry_overdue')
            ->with([
                'user' => $this->user,
                'question' => $this->question,
                'token' => $this->token,
                'app_url' => $this->app_url,
            ])
            ->subject('Enquiry Overdue');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        $files = $this->question->attachments ?? [];

        if (is_array($files)) {
            foreach ($files as $file) {
                if (!empty($file['url'])) {
                    $attachments[] = Attachment::fromPath($file['url']);
                }
            }
        } else if(is_string($files)) {
            if (!empty($files)) {
                $attachments[] = Attachment::fromPath($files);
            }
        }

        return $attachments;
    }
}
