<?php
declare(strict_types=1);

namespace App\Mail;

use App\Models\Question;
use App\Models\National;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class QuestionCreatedNational extends Mailable
{
    use Queueable, SerializesModels;

    private Question $question;
    private National $national;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Question $question, National $national)
    {
        $this->question = $question;
        $this->national = $national;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        $contractor_name = $this->question->getContractorName();

        return $this->view('mail.newquestion_national')->with([
            'national' => $this->national,
            'question' => $this->question,
        ])->subject("New Enquiry from $contractor_name via The Build Chain Platform");
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
