# Book PDF Import — Usage Guide

`app:import-book-pages` splits a PDF into individual page images, uploads each
page to the media API, and saves one `book_pages` row per page (`book_id`,
`page_number`, `image_path`, `image_url`). It does **not** run OCR — that's a
separate step (`app:ocr-book-pages`) that reads the uploaded `image_url` and
fills in `ai_ocr_content` afterward.

```text
PDF → render page (Imagick + Ghostscript) → upload image → book_pages row
```

## Prerequisites

- `ghostscript` installed (`brew install ghostscript` on macOS) — required by
  Imagick to rasterize PDF pages.
- The PDF file placed in `storage/app/books/`.

## 1. Put the PDF in place

```bash
cp ~/Downloads/example.pdf storage/app/books/example.pdf
```

## 2. Run the command

```bash
php artisan app:import-book-pages example.pdf "Book Name" --writer="Writer Name"
```

This will:

1. Find or create a row in `books` matching `(name, writer_name)`.
2. Slugify the book name into an upload folder (e.g. "Book Name" →
   `book-name`), falling back to `book-{id}` if the slug is empty/generic.
3. Render every page at `BOOK_PAGE_RENDER_DPI` (default `300`, see `.env`),
   upload it, and upsert the matching `book_pages` row.

## Options

| Option | Meaning |
|---|---|
| `--writer=` | Author/writer name (optional) |
| `--book-id=` | Reuse an existing book id explicitly instead of matching by name/writer |
| `--start-page=` | First page to process, 1-based inclusive (default `1`) |
| `--end-page=` | Last page to process, 1-based inclusive (default: last page in PDF) |

Process only a page range:

```bash
php artisan app:import-book-pages example.pdf "Book Name" --writer="Writer Name" \
    --start-page=1 --end-page=50
```

Reuse an existing book id (e.g. to add more pages to a book created earlier):

```bash
php artisan app:import-book-pages example-part2.pdf "Book Name" --book-id=3
```

## Resuming after an interruption

Just re-run the exact same command. Every page is checked against
`(book_id, page_number)` before it's rendered/uploaded — already-saved pages
are skipped, so processing continues where it left off (even after a crash,
Ctrl+C, or a page that failed to upload).

```text
Book: Example Book
Total pages: 50 (PDF has 50 pages)

 50/50 [============================] 100%

Done.
Saved: 48
Skipped: 0
Failed: 2
```

A non-zero `Failed` count means those specific pages weren't saved — re-run
the same command and they'll be retried automatically.

## After importing: run OCR

Once pages are imported (i.e. `image_url` is populated), run the existing
AI OCR command to fill in `ai_ocr_content`:

```bash
php artisan app:ocr-book-pages
```

Then, once a book's pages are OCR'd, organize it into topics:

```bash
php artisan app:organize-book-topics {book_id}
```

## Troubleshooting

- **`PDF not found: storage/app/books/...`** — check the filename matches
  exactly what's in `storage/app/books/`.
- **`Media upload failed with status 5xx`** — the external media API
  (`MEDIA_UPLOAD_BASE_URL` in `.env`) is unreachable or erroring. This is an
  upstream/network issue, not a code bug — just re-run the command once the
  API is back up; failed pages are retried automatically.
- Config: `BOOK_PAGE_RENDER_DPI`, `MEDIA_UPLOAD_BASE_URL`,
  `MEDIA_UPLOAD_TIMEOUT` in `.env` (see `config/books.php` and
  `config/services.php`).


`php artisan app:import-book-pages book-1.pdf "গল্পে আঁকা সীরাত হে মুহাম্মদ" --writer="ইয়াহইয়া ইউসুফ নদভী"`
