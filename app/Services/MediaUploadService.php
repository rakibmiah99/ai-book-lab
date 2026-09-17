<?php

namespace App\Services;

use App\Exceptions\MediaUploadException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MediaUploadService
{
    /**
     * Upload a single page image and return its stored path and public URL.
     *
     * @return array{path: string, url: string}
     *
     * @throws MediaUploadException
     */
    public function upload(string $imageContents, string $folder, string $filename): array
    {
        $baseUrl = rtrim(config('services.media_upload.base_url'), '/');

        $response = Http::timeout(config('services.media_upload.timeout', 30))
            ->attach('image', $imageContents, "{$filename}.png", ['Content-Type' => 'image/png'])
            ->post("{$baseUrl}/api/media/upload", [
                'folder' => $folder,
                'type' => 'file',
                'filename' => $filename,
            ]);

        if ($response->status() !== 201) {
            throw new MediaUploadException(
                "Media upload failed with status {$response->status()}: ".substr($response->body(), 0, 500)
            );
        }

        $body = $response->json();

        if (! isset($body['path'], $body['url'])) {
            throw new MediaUploadException('Unexpected media upload response: '.substr($response->body(), 0, 500));
        }

        return ['path' => $body['path'], 'url' => $body['url']];
    }

    /**
     * Turn a book name into a folder path safe for the upload API, falling
     * back to `book-{id}` when the slugified name is empty or the generic "book".
     */
    public function slugifyFolder(string $name, int $fallbackBookId): string
    {
        $slug = Str::slug($name);

        return $slug === '' || $slug === 'book' ? "book-{$fallbackBookId}" : $slug;
    }
}
