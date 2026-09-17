<?php

namespace App\Console\Commands;

use App\Ai\Agents\BookTableOfContentsAgent;
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
#[Description("Organize a book's OCR content into topics and reference pages")]
class OrganizeBookTopics extends Command
{
    /**
     * The number of consecutive book pages sent to an agent per call, so the
     * prompt stays well within the model's input token limit regardless of
     * how long the book is.
     */
    protected const PAGES_PER_CHUNK = 10;

    /**
     * The number of opening pages scanned when looking for the book's own
     * printed table of contents.
     */
    protected const TOC_SCAN_PAGE_LIMIT = 20;

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

        $pages = $book->pages()
            ->whereNotNull('ai_ocr_content')
            ->orderBy('page_number')
            ->get();

        if ($pages->isEmpty()) {
            $this->info("Book [{$book->id}] has no OCR'd pages to organize.");

            return self::SUCCESS;
        }

        try {
            if (! $book->topics()->exists()) {
                $this->createTopicsFromTableOfContents($book, $pages);

                $book->refresh();
            }

            if (! $book->topics()->exists() || ! is_null($book->topics_organized_through_page)) {
                $this->organizeWithoutTableOfContents($book, $pages);
            }
        } catch (Throwable $e) {
            $this->error("Failed to organize topics for book [{$book->id}]: {$e->getMessage()}");
            $this->logProviderError($book, $e);

            return self::FAILURE;
        }

        $this->info("Book [{$book->id}] organized into topics.");

        return self::SUCCESS;
    }

    /**
     * Look for the book's own printed table of contents in its opening pages
     * and, if found, create the book's topics and reference pages from it —
     * so topics match the book's real structure instead of arbitrary page
     * chunks. Leaves no topics behind if no table of contents is found, so
     * the caller can fall back to page-chunk topic discovery.
     *
     * @param  Collection<int, BookPage>  $pages
     */
    protected function createTopicsFromTableOfContents(Book $book, Collection $pages): void
    {
        /** @var StructuredAgentResponse $response */
        $response = (new BookTableOfContentsAgent)->prompt(
            $this->transcriptPrompt($pages->take(self::TOC_SCAN_PAGE_LIMIT)),
        );

        $entries = collect($response['entries'])->sortBy('position')->values();

        if ($entries->isEmpty()) {
            $this->info("Book [{$book->id}]: no table of contents found; falling back to page-chunk topic discovery.");

            return;
        }

        $pagesByNumber = $pages->keyBy('page_number');
        $lastPageNumber = $pagesByNumber->keys()->max();

        DB::transaction(function () use ($book, $entries, $pagesByNumber, $lastPageNumber) {
            foreach ($entries as $index => $entry) {
                $startPage = $entry['page_number'];
                $nextEntry = $entries->get($index + 1);
                $endPage = $nextEntry ? $nextEntry['page_number'] - 1 : $lastPageNumber;

                $bookTopic = $book->topics()->create([
                    'name' => $entry['name'],
                    'slug' => Str::slug($entry['name']).'-'.$entry['position'],
                    'position' => $entry['position'],
                ]);

                $pagesByNumber
                    ->filter(fn (BookPage $page, int $pageNumber) => $pageNumber >= $startPage && $pageNumber <= $endPage)
                    ->each(fn (BookPage $page) => $bookTopic->refPages()->create(['book_page_id' => $page->id]));
            }
        });

        $this->info("Book [{$book->id}]: created ".$entries->count().' topic(s) from the table of contents.');
    }

    /**
     * Fall back to discovering topics directly from fixed-size page chunks,
     * for books with no printed table of contents. Resumable via the book's
     * `topics_organized_through_page` pointer, which this path is the only
     * one to ever set.
     *
     * @param  Collection<int, BookPage>  $pages
     */
    protected function organizeWithoutTableOfContents(Book $book, Collection $pages): void
    {
        $organizedThroughPage = $book->topics_organized_through_page ?? 0;

        $remainingPages = $pages->where('page_number', '>', $organizedThroughPage)->values();

        if ($remainingPages->isEmpty()) {
            return;
        }

        $pagesByNumber = $pages->keyBy('page_number');
        $position = ((int) $book->topics()->max('position')) + 1;

        foreach ($remainingPages->chunk(self::PAGES_PER_CHUNK) as $chunk) {
            /** @var StructuredAgentResponse $response */
            $response = (new BookTopicOrganizerAgent)->prompt(
                $this->transcriptPrompt($chunk),
            );

            $chunkTopics = [];

            foreach ($response['topics'] as $topicStructure) {
                $chunkTopics[] = [
                    ...$topicStructure,
                    'position' => $position++,
                ];
            }

            $lastPageNumber = $chunk->max('page_number');

            $this->storeChunk($book, $pagesByNumber, $chunkTopics, $lastPageNumber);

            $this->info("Book [{$book->id}]: organized through page {$lastPageNumber} — found ".count($chunkTopics).' topic(s) in this chunk.');
        }
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
     * Persist one chunk's topics for the given book and advance the book's
     * resume pointer to the chunk's last page, atomically. This lets a
     * re-run of the command pick up from the next chunk instead of
     * re-processing (and re-billing) pages that are already organized.
     *
     * @param  Collection<int, BookPage>  $pagesByNumber
     * @param  array<int, array{name: string, position: int, ref_page_numbers: array<int, int>}>  $topics
     */
    protected function storeChunk(Book $book, Collection $pagesByNumber, array $topics, int $lastPageNumber): void
    {
        DB::transaction(function () use ($book, $pagesByNumber, $topics, $lastPageNumber) {
            foreach ($topics as $topic) {
                $bookTopic = $book->topics()->create([
                    'name' => $topic['name'],
                    'slug' => Str::slug($topic['name']).'-'.$topic['position'],
                    'position' => $topic['position'],
                ]);

                foreach ($topic['ref_page_numbers'] as $pageNumber) {
                    $page = $pagesByNumber->get($pageNumber);

                    if ($page) {
                        $bookTopic->refPages()->create(['book_page_id' => $page->id]);
                    }
                }
            }

            $book->update(['topics_organized_through_page' => $lastPageNumber]);
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
