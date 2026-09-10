<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ActivityTracker;
use App\Models\ActivityTrackerProcessedQuote;
use App\Models\ActivityTrackerTotal;
use App\Models\Answer;
use App\Repository\AnswerRepository;
use App\Repository\UserRepository;
use Bnb\Laravel\Attachments\Attachment;
use Illuminate\Console\Command;
use Orhanerday\OpenAi\OpenAi;
use Karkow\MuPdf\Pdf;

class ConvertPdfActivityTracker extends Command
{
    protected $signature = 'import:pdf {quotes?*}';
    protected $description = 'Import pdf activity tracker';
    private OpenAi $openai;
    private AnswerRepository $answerRepository;
    private UserRepository $userRepository;

    public function __construct(AnswerRepository $answerRepository, UserRepository $userRepository)
    {
        parent::__construct();

        $this->answerRepository = $answerRepository;
        $this->userRepository = $userRepository;

        $this->openai = new OpenAi(config('openai.key', ''));
    }

    public function handle(): void
    {
        $quotes = explode(',', $this->argument('quotes')[0]);

        if (!empty($quotes)) {
            // Remove ActivityTrackerProcessedQuote records for the specified quotes
            ActivityTrackerProcessedQuote::whereIn('quote_id', $quotes)->delete();
            $quotes = $this->answerRepository->getQuotesByIds($quotes);
        } else {
            $quotes = $this->answerRepository->getQuotesToProcessActivityTracker();
        }

        $this->line( 'Quotes: ' . count($quotes) );

        $quotesToDelete = array_map(function ($quote) {
            return $quote['id'];
        }, $quotes->toArray());
        if (count($quotesToDelete) > 0) {
            ActivityTracker::whereIn('quote_id', $quotesToDelete)->delete();
        }

        /** @var Answer $quote */
        foreach ($quotes as $quote) {
            $this->line("processing " . $quote->id);
            $user = $this->userRepository->getUserById($quote->user_id);
            $attachments = $quote->attachments()->get();

            if (empty($user->activity_tracker_mapping)) {
                $this->line("no activity tracker mapping");
                continue;
            }

            $error = '';
            try {
                foreach ($attachments as $attachment) {
                    $this->processFile($attachment, $quote->id, $user->activity_tracker_mapping);
                }
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }

            $this->line($error);

            ActivityTrackerProcessedQuote::create([
                'inquiry_type' => Answer::class,
                'quote_id' => $quote->id,
                'error_message' => $error,
            ]);
        }
    }


    /**
     * @throws \Exception
     */
    public function processFile(Attachment $attachment, $quoteId, $mapping = ''): void
    {
        if (empty($mapping)) {
            $mapping = "('QTY' or 'Qty' or 'Ordered') as 'qty', 'Item Code' as 'item_code', ".
            "'DESCRIPTION' as 'desc', ('PRICE AND TERMS' or 'Unit Price' or 'Amount GBP' or 'Total' or 'Line Total') as price, (price as 'unit_price'),
            (take unit from price as 'unit'), ".
            "('VALUE(EXC. VAT)' or 'Net Amount') as 'amount_without_vat', 'VAT RATE' as 'vat_rate'";
        }

        $sourceFile = storage_path('app') . '/' . $attachment->filepath;
        if (!str_contains($sourceFile, '.pdf') || !file_exists($sourceFile)) {
            $this->line("File $sourceFile is invalid.");
            return;
        }

        // Use MuPDF for better text preservation
        ///root/mupdf/build/release/mutool
        $mupdf = new Pdf($sourceFile, '/bin/mutool');
        $pageCount = $mupdf->numberOfPages();

        $imageFiles = [];
        for ($page = 1; $page <= $pageCount; $page++) {
            $imagePath = sys_get_temp_dir() . '/' . $attachment->filename . "_page{$page}.png";

            $mupdf->setPage($page);
            $mupdf->setOutputFormat('png');
            $mupdf->saveImage($imagePath);

            $imageFiles[] = $imagePath;
        }

        $this->line("Generated image files: " . implode(', ', $imageFiles));

        $messages = [
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "text",
                        "text" => "You are a professional data extraction assistant. Given the following images of a table (one image per page, skip the ones without table records with quantity, price)" .
                        "extract all rows and columns as accurately as possible. Return the data as a single JSON object {\"items\": [], \"total\": 0} where " .
                        "items is an array of objects, total - a single total from the document.".
                        "Each item represents a row and each key matches the column names or mapping provided: $mapping. If a value is missing ".
                        "or unclear, use null. Do not include any explanations, comments, or extra formatting—return only valid JSON. ".
                        "The table is always there, review files carefully. If the table is not found or cannot be read, return an empty JSON array []."
                    ],
                ]
            ]
        ];
        $deleteFiles = [];
        foreach ($imageFiles as $imageFile) {
            echo $imageFile . "\n";
            $path = public_path('temp-images/' . basename($imageFile));
            file_put_contents($path, file_get_contents($imageFile));
            $url = asset('temp-images/' . basename($imageFile));
            echo "$url\n";

            //echo base64_encode(file_get_contents($imageFile)) . "\n\n";
            $messages[0]["content"][] = [
                "type" => "image_url",
                "image_url" => [
                    "url" => $url, //'data:image/png;base64,' . base64_encode(file_get_contents($imageFile))
                ]
            ];
            $deleteFiles[] = $path;
        }

        $response = $this->openai->chat([
            "model" => "gpt-4.1",
            "messages" => $messages,
            "max_completion_tokens" => 15000,
        ]);

        foreach ($deleteFiles as $deleteFile) {
            unlink($deleteFile);
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'];

        $this->line($content);

        if (is_string($content)) {
            $content = str_replace('```json', '', $content);
            $content = str_replace('```', '', $content);

            $result = json_decode($content, true) ?? ['items' => [], 'total' => 0];
        } else {
            $result = $content;
        }

        ActivityTrackerTotal::create([
            'type' => Answer::class,
            'quote_id' => $quoteId,
            'file' => $attachment->filepath,
            'total' => (float)($result['total'] ?? 0),
        ]);

        foreach (($result['items'] ?? []) as $activityTrackerRecord) {
            $qty = $activityTrackerRecord['qty'] ?? null;
            if (is_string($qty)) {
                $qty = (float)(str_replace(',', '', $activityTrackerRecord['qty']));
            }

            ActivityTracker::create([
                'type' => Answer::class,
                'quote_id' => $quoteId,
                'desc' => $activityTrackerRecord['desc'] ?? null,
                'item_no' => $activityTrackerRecord['item_no'] ?? null,
                'item_code' => $activityTrackerRecord['item_code'] ?? null,
                'qty' => $qty,
                'unit' => $activityTrackerRecord['unit'] ?? null,
                'price_and_terms' => $activityTrackerRecord['price_and_terms'] ?? null,
                'unit_price' => $activityTrackerRecord['unit_price'] ?? $activityTrackerRecord['price'] ?? null,
                'disc_percent' => $activityTrackerRecord['disc_percent'] ?? null,
                'per_unit' => $activityTrackerRecord['per_unit'] ?? null,
                'amount' => $activityTrackerRecord['amount'] ?? null,
                'vat' => $activityTrackerRecord['vat'] ?? $activityTrackerRecord['vat_rate'] ?? null,
                'file' => $attachment->filepath,
            ]);
        }
    }
}
