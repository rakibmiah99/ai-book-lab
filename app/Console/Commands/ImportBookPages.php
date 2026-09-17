<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\BookPage;
use App\Services\MediaUploadService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\PdfToImage\Enums\OutputFormat;
use Spatie\PdfToImage\Exceptions\PdfDoesNotExist;
use Spatie\PdfToImage\Pdf;
use Throwable;

#[Signature('app:import-book-pages
    {pdf : PDF filename inside storage/app/books, e.g. example.pdf}
    {name : Book title to store in the books table}
    {--writer= : Author/writer name}
    {--book-id= : Reuse an existing book id explicitly}
    {--start-page=1 : First page to process (1-based, inclusive)}
    {--end-page= : Last page to process (1-based, inclusive)}'
)]
#[Description('Split a PDF into pages, upload each page image, and save book_pages rows (no OCR — that runs separately via app:ocr-book-pages)')]
class ImportBookPages extends Command
{
    protected int $saved = 0;

    protected int $skipped = 0;

    protected int $failed = 0;

    /**
     * Execute the console command.
     */
    public function handle(MediaUploadService $mediaUpload): int
    {
        $pdfPath = storage_path('app/books/'.$this->argument('pdf'));

        if (! is_file($pdfPath)) {
            $this->error("PDF not found: storage/app/books/{$this->argument('pdf')}");

            return self::FAILURE;
        }

        $book = $this->resolveBook();

        if ($book === null) {
            return self::FAILURE;
        }

        try {
            $pdf = new Pdf($pdfPath);
            $totalPagesInPdf = $pdf->pageCount();
        } catch (PdfDoesNotExist $e) {
            $this->error("Failed to open PDF: {$e->getMessage()}");

            return self::FAILURE;
        }

        $firstPage = max(1, (int) $this->option('start-page'));
        $lastPage = $this->option('end-page')
            ? min($totalPagesInPdf, (int) $this->option('end-page'))
            : $totalPagesInPdf;
        $totalToProcess = max(0, $lastPage - $firstPage + 1);

        $folder = $mediaUpload->slugifyFolder($book->name, $book->id);

        $this->info("Book: {$book->name}");
        $this->info("Total pages: {$totalToProcess} (PDF has {$totalPagesInPdf} pages)");
        $this->newLine();

        $pdf->resolution((int) config('books.render_dpi'))->format(OutputFormat::Png);

        $this->output->progressStart($totalToProcess);

        for ($pageNumber = $firstPage; $pageNumber <= $lastPage; $pageNumber++) {
            $this->processPage($pdf, $book, $folder, $pageNumber, $mediaUpload);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info('Done.');
        $this->info("Saved: {$this->saved}");
        $this->info("Skipped: {$this->skipped}");
        $this->info("Failed: {$this->failed}");

        return $this->failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Resolve the book row: reuse an explicit --book-id, or find/create by (name, writer).
     */
    protected function resolveBook(): ?Book
    {
        if ($bookId = $this->option('book-id')) {
            $book = Book::find($bookId);

            if ($book === null) {
                $this->error("No book found with id={$bookId}");

                return null;
            }

            return $book;
        }

        return Book::firstOrCreate([
            'name' => $this->argument('name'),
            'writer_name' => $this->option('writer'),
        ]);
    }

    /**
     * Render, upload, and save a single page — skipping it if already saved (resume support).
     */
    protected function processPage(Pdf $pdf, Book $book, string $folder, int $pageNumber, MediaUploadService $mediaUpload): void
    {
        $exists = BookPage::where('book_id', $book->id)
            ->where('page_number', $pageNumber)
            ->exists();

        if ($exists) {
            $this->skipped++;

            return;
        }

        try {
            $imagick = $pdf->getImageData('page.png', $pageNumber);
            $imageContents = $imagick->getImageBlob();
            $imagick->clear();

            $uploaded = $mediaUpload->upload($imageContents, $folder, "page-{$pageNumber}");

            BookPage::updateOrCreate(
                ['book_id' => $book->id, 'page_number' => $pageNumber],
                ['content' => '', 'image_path' => $uploaded['path'], 'image_url' => $uploaded['url']]
            );

            $this->saved++;
        } catch (Throwable $e) {
            $this->failed++;
            $this->newLine();
            $this->error("Failed on page {$pageNumber}: {$e->getMessage()}");
        }
    }
}
