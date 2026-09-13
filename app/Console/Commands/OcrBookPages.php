<?php

namespace App\Console\Commands;

use App\Ai\Agents\BookPageOcrAgent;
use App\Models\BookPage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

#[Signature('app:ocr-book-pages')]
#[Description('Read pending book page images with the OCR agent and store the extracted text')]
class OcrBookPages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pending = BookPage::whereNull('ai_ocr_content')
            ->whereNotNull('image_url')
            ->count();

        if ($pending === 0) {
            $this->info('No book pages pending OCR.');

            return self::SUCCESS;
        }

        $this->output->progressStart($pending);

        BookPage::whereNull('ai_ocr_content')
            ->whereNotNull('image_url')
            ->chunkById(20, function ($pages) {
                foreach ($pages as $page) {
                    $this->ocrPage($page);
                    $this->output->progressAdvance();
                }
            });

        $this->output->progressFinish();

        return self::SUCCESS;
    }

    /**
     * Read the given book page's image and store the transcribed text.
     */
    protected function ocrPage(BookPage $page): void
    {
        try {
            /** @var StructuredAgentResponse $response */
            $response = (new BookPageOcrAgent)->prompt(
                'Transcribe the text visible in this book page image.',
                attachments: [Image::fromUrl($page->image_url)],
            );

            $page->update(['ai_ocr_content' => trim($response['ocr_content'])]);
        } catch (Throwable $e) {
            $this->newLine();
            $this->error("Failed to OCR book page [{$page->id}]: {$e->getMessage()}");
            $this->logProviderError($page, $e);
        }
    }

    /**
     * Log the underlying AI provider error, including the raw HTTP response when available.
     */
    protected function logProviderError(BookPage $page, Throwable $e): void
    {
        $context = [
            'book_page_id' => $page->id,
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ];

        $requestException = $e->getPrevious();

        if ($requestException instanceof RequestException) {
            $context['status'] = $requestException->response->status();
            $context['headers'] = $requestException->response->headers();
            $context['body'] = $requestException->response->json() ?? $requestException->response->body();
        }

        Log::channel('ai_provider')->error('AI provider request failed', $context);
    }
}
