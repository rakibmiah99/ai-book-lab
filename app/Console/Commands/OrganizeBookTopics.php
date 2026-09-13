<?php

namespace App\Console\Commands;

use App\Ai\Agents\BookTopicOrganizerAgent;
use App\Models\Book;
use App\Models\BookPage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

#[Signature('app:organize-book-topics {book_id : The ID of the book to organize}')]
#[Description("Organize a book's OCR content into topics, details, reference pages, and important notes")]
class OrganizeBookTopics extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $book = Book::find($this->argument('book_id'));

        if (! $book) {
            $this->error("Book [{$this->argument('book_id')}] not found.");

            return self::FAILURE;
        }

        if ($book->topics()->exists()) {
            $this->info("Book [{$book->id}] has already been organized into topics.");

            return self::SUCCESS;
        }

        $pages = $book->pages()
            ->whereNotNull('ai_ocr_content')
            ->orderBy('page_number')
            ->get();

        if ($pages->isEmpty()) {
            $this->info("Book [{$book->id}] has no OCR'd pages to organize.");

            return self::SUCCESS;
        }

        try {
            /** @var StructuredAgentResponse $response */
            $response = (new BookTopicOrganizerAgent)->prompt(
                $this->transcriptPrompt($pages),
            );

            $this->storeTopics($book, $pages, $response['topics']);
        } catch (Throwable $e) {
            $this->error("Failed to organize topics for book [{$book->id}]: {$e->getMessage()}");
            $this->logProviderError($book, $e);

            return self::FAILURE;
        }

        $this->info("Book [{$book->id}] organized into topics.");

        return self::SUCCESS;
    }

    /**
     * Build the transcript prompt from the book's OCR'd pages.
     *
     * @param  Collection<int, BookPage>  $pages
     */
    protected function transcriptPrompt(Collection $pages): string
    {
        $transcript = $pages->map(
            fn (BookPage $page) => "[Page {$page->page_number}]\n{$page->ai_ocr_content}"
        )->implode("\n\n");

        return <<<TEXT
        Organize the following book transcript into topics.

        {$transcript}
        TEXT;
    }

    /**
     * Persist the agent's structured topics for the given book.
     *
     * @param  Collection<int, BookPage>  $pages
     * @param  array<int, array{name: string, position: int, ref_page_numbers: array<int, int>, details: array<int, array{content: string, position: int}>, important_notes: array<int, array{note: string, punch_line: string}>}>  $topics
     */
    protected function storeTopics(Book $book, Collection $pages, array $topics): void
    {
        $pagesByNumber = $pages->keyBy('page_number');

        DB::transaction(function () use ($book, $pagesByNumber, $topics) {
            foreach ($topics as $topic) {
                $bookTopic = $book->topics()->create([
                    'name' => $topic['name'],
                    'slug' => Str::slug($topic['name']).'-'.$topic['position'],
                    'position' => $topic['position'],
                ]);

                foreach ($topic['details'] as $detail) {
                    $bookTopic->details()->create($detail);
                }

                foreach ($topic['important_notes'] as $note) {
                    $bookTopic->importantNotes()->create($note);
                }

                foreach ($topic['ref_page_numbers'] as $pageNumber) {
                    $page = $pagesByNumber->get($pageNumber);

                    if ($page) {
                        $bookTopic->refPages()->create(['book_page_id' => $page->id]);
                    }
                }
            }
        });
    }

    /**
     * Log the underlying AI provider error, including the raw HTTP response when available.
     */
    protected function logProviderError(Book $book, Throwable $e): void
    {
        $context = [
            'book_id' => $book->id,
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
