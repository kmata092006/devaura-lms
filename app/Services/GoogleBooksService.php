<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleBooksService
{
    /**
     * Searches for book details using Google Books first, then Open Library as fallback.
     */
    public function search(string $searchTerm): array
    {
        $searchTerm = trim($searchTerm);

        if ($searchTerm === '') {
            return [
                'success' => false,
                'message' => 'Please enter an ISBN or book title.',
                'books' => [],
            ];
        }

        $cacheKey = 'book_lookup_' . md5(strtolower($searchTerm));

        // Caches lookup results to reduce repeated API requests for the same search term.
        return Cache::remember($cacheKey, now()->addHours(12), function () use ($searchTerm) {
            $googleResult = $this->searchGoogleBooks($searchTerm);

            if ($googleResult['success']) {
                return $googleResult;
            }

            $openLibraryResult = $this->searchOpenLibrary($searchTerm);

            if ($openLibraryResult['success']) {
                $openLibraryResult['message'] = 'Book details found using fallback source. Please review before saving.';
                return $openLibraryResult;
            }

            return [
                'success' => false,
                'message' => $googleResult['message'] ?? 'No book found. Please add the book manually.',
                'books' => [],
            ];
        });
    }

    /**
     * Searches Google Books API and returns normalized book results.
     */
    private function searchGoogleBooks(string $searchTerm): array
    {
        $query = $this->buildGoogleQuery($searchTerm);

        try {
            // Prepares Google Books API parameters.
            $params = [
                'q' => $query,
                'maxResults' => 5,
                'printType' => 'books',
                'projection' => 'lite',
            ];

            $apiKey = config('services.google_books.key');

            // Adds the API key only when configured in the application settings.
            if (!empty($apiKey)) {
                $params['key'] = $apiKey;
            }

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get('https://www.googleapis.com/books/v1/volumes', $params);

            if (!$response->successful()) {
                // Logs unsuccessful Google Books responses for debugging.
                Log::warning('Google Books API unsuccessful response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                    'has_api_key' => !empty($apiKey),
                ]);

                return [
                    'success' => false,
                    'message' => 'Google Books lookup returned HTTP status ' . $response->status() . '. Trying fallback source.',
                    'books' => [],
                ];
            }

            $data = $response->json();

            if (empty($data['items'])) {
                return [
                    'success' => false,
                    'message' => 'No Google Books result found. Trying fallback source.',
                    'books' => [],
                ];
            }

            // Converts raw Google Books results into the system's book field format.
            $books = collect($data['items'])
                ->map(fn ($item) => $this->normalizeGoogleBook($item))
                ->filter(fn ($book) => !empty($book['book_title']))
                ->values()
                ->all();

            if (empty($books)) {
                return [
                    'success' => false,
                    'message' => 'No usable Google Books details found. Trying fallback source.',
                    'books' => [],
                ];
            }

            return [
                'success' => true,
                'message' => 'Book details found using Google Books.',
                'books' => $books,
            ];
        } catch (\Throwable $exception) {
            // Logs connection, timeout, SSL, or unexpected Google Books errors.
            Log::error('Google Books lookup failed', [
                'message' => $exception->getMessage(),
                'query' => $query,
            ]);

            return [
                'success' => false,
                'message' => 'Google Books lookup failed. Trying fallback source.',
                'books' => [],
            ];
        }
    }

    /**
     * Searches Open Library when Google Books has no usable result.
     */
    private function searchOpenLibrary(string $searchTerm): array
    {
        $cleanedIsbn = $this->cleanIsbn($searchTerm);

        try {
            // Uses the direct ISBN endpoint first when the search term is a valid ISBN.
            if ($cleanedIsbn && (strlen($cleanedIsbn) === 10 || strlen($cleanedIsbn) === 13)) {
                $response = Http::timeout(15)
                    ->withoutVerifying()
                    ->acceptJson()
                    ->get('https://openlibrary.org/isbn/' . $cleanedIsbn . '.json');

                if ($response->successful()) {
                    $book = $this->normalizeOpenLibraryIsbnBook($response->json(), $cleanedIsbn);

                    if (!empty($book['book_title'])) {
                        return [
                            'success' => true,
                            'message' => 'Book details found using Open Library.',
                            'books' => [$book],
                        ];
                    }
                }

                Log::warning('Open Library ISBN lookup unsuccessful', [
                    'status' => $response->status(),
                    'isbn' => $cleanedIsbn,
                    'body' => $response->body(),
                ]);
            }

            // Falls back to Open Library keyword search for title-based searches.
            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get('https://openlibrary.org/search.json', [
                    'q' => $searchTerm,
                    'limit' => 5,
                ]);

            if (!$response->successful()) {
                // Logs unsuccessful fallback responses for troubleshooting.
                Log::warning('Open Library search unsuccessful', [
                    'status' => $response->status(),
                    'query' => $searchTerm,
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Fallback lookup is currently unavailable. Please add the book manually.',
                    'books' => [],
                ];
            }

            $data = $response->json();

            if (empty($data['docs'])) {
                return [
                    'success' => false,
                    'message' => 'No book found. Please try another ISBN/title or add the book manually.',
                    'books' => [],
                ];
            }

            // Converts Open Library search results into the same format used by Google Books.
            $books = collect($data['docs'])
                ->map(fn ($item) => $this->normalizeOpenLibrarySearchBook($item))
                ->filter(fn ($book) => !empty($book['book_title']))
                ->values()
                ->all();

            if (empty($books)) {
                return [
                    'success' => false,
                    'message' => 'No usable fallback book details found. Please add the book manually.',
                    'books' => [],
                ];
            }

            return [
                'success' => true,
                'message' => 'Book details found using Open Library.',
                'books' => $books,
            ];
        } catch (\Throwable $exception) {
            // Logs connection, timeout, SSL, or unexpected Open Library errors.
            Log::error('Open Library lookup failed', [
                'message' => $exception->getMessage(),
                'query' => $searchTerm,
            ]);

            return [
                'success' => false,
                'message' => 'Book lookup failed: ' . $exception->getMessage(),
                'books' => [],
            ];
        }
    }

    /**
     * Builds a Google Books query and detects ISBN-based searches.
     */
    private function buildGoogleQuery(string $searchTerm): string
    {
        $cleaned = $this->cleanIsbn($searchTerm);

        if ($cleaned && (strlen($cleaned) === 10 || strlen($cleaned) === 13)) {
            return 'isbn:' . strtoupper($cleaned);
        }

        return $searchTerm;
    }

    /**
     * Converts one Google Books API result into the system's book array format.
     */
    private function normalizeGoogleBook(array $item): array
    {
        $volumeInfo = $item['volumeInfo'] ?? [];

        $title = trim($volumeInfo['title'] ?? '');
        $subtitle = trim($volumeInfo['subtitle'] ?? '');

        $bookTitle = $title;

        if ($subtitle !== '') {
            $bookTitle .= ': ' . $subtitle;
        }

        $authors = $volumeInfo['authors'] ?? [];
        $categories = $volumeInfo['categories'] ?? [];

        return [
            'source' => 'Google Books',
            'google_books_id' => $item['id'] ?? null,
            'book_title' => $bookTitle,
            'isbn' => $this->extractGoogleIsbn($volumeInfo['industryIdentifiers'] ?? []),
            'author_name' => $authors[0] ?? '',
            'authors' => $authors,
            'publisher_name' => $volumeInfo['publisher'] ?? '',
            'date_published' => $this->normalizePublishedDate($volumeInfo['publishedDate'] ?? null),
            'genre_name' => $categories[0] ?? '',
            'description' => $volumeInfo['description'] ?? '',
            'thumbnail' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
            'preview_link' => $volumeInfo['previewLink'] ?? '',
        ];
    }

    /**
     * Converts an Open Library ISBN response into the system's book array format.
     */
    private function normalizeOpenLibraryIsbnBook(array $item, string $isbn): array
    {
        $title = trim($item['title'] ?? '');

        $authors = [];

        // Extracts author identifiers from Open Library author references.
        if (!empty($item['authors']) && is_array($item['authors'])) {
            foreach ($item['authors'] as $author) {
                if (!empty($author['key'])) {
                    $authors[] = basename($author['key']);
                }
            }
        }

        $publisherName = '';

        if (!empty($item['publishers']) && is_array($item['publishers'])) {
            $publisherName = $item['publishers'][0] ?? '';
        }

        $genreName = '';

        if (!empty($item['subjects']) && is_array($item['subjects'])) {
            $genreName = $item['subjects'][0] ?? '';
        }

        return [
            'source' => 'Open Library',
            'google_books_id' => null,
            'book_title' => $title,
            'isbn' => $isbn,
            'author_name' => $authors[0] ?? '',
            'authors' => $authors,
            'publisher_name' => $publisherName,
            'date_published' => $this->normalizePublishedDateFromOpenLibrary($item['publish_date'] ?? null),
            'genre_name' => $genreName,
            'description' => $this->extractOpenLibraryDescription($item['description'] ?? null),
            'thumbnail' => $isbn ? 'https://covers.openlibrary.org/b/isbn/' . $isbn . '-M.jpg' : '',
            'preview_link' => '',
        ];
    }

    /**
     * Converts an Open Library search result into the system's book array format.
     */
    private function normalizeOpenLibrarySearchBook(array $item): array
    {
        $isbn = '';

        if (!empty($item['isbn']) && is_array($item['isbn'])) {
            $isbn = $this->chooseBestIsbn($item['isbn']);
        }

        $authors = $item['author_name'] ?? [];
        $publishers = $item['publisher'] ?? [];
        $subjects = $item['subject'] ?? [];

        return [
            'source' => 'Open Library',
            'google_books_id' => null,
            'book_title' => $item['title'] ?? '',
            'isbn' => $isbn,
            'author_name' => $authors[0] ?? '',
            'authors' => $authors,
            'publisher_name' => $publishers[0] ?? '',
            'date_published' => $this->normalizePublishedYear($item['first_publish_year'] ?? null),
            'genre_name' => $subjects[0] ?? '',
            'description' => '',
            'thumbnail' => $isbn ? 'https://covers.openlibrary.org/b/isbn/' . $isbn . '-M.jpg' : '',
            'preview_link' => !empty($item['key']) ? 'https://openlibrary.org' . $item['key'] : '',
        ];
    }

    /**
     * Extracts the best available Google Books ISBN, prioritizing ISBN-13 over ISBN-10.
     */
    private function extractGoogleIsbn(array $identifiers): string
    {
        $isbn13 = null;
        $isbn10 = null;

        foreach ($identifiers as $identifier) {
            if (($identifier['type'] ?? '') === 'ISBN_13') {
                $isbn13 = $identifier['identifier'] ?? null;
            }

            if (($identifier['type'] ?? '') === 'ISBN_10') {
                $isbn10 = $identifier['identifier'] ?? null;
            }
        }

        return $this->cleanIsbn($isbn13 ?: $isbn10 ?: '') ?? '';
    }

    /**
     * Chooses the best ISBN from a list, preferring ISBN-13 when available.
     */
    private function chooseBestIsbn(array $isbns): string
    {
        $cleaned = collect($isbns)
            ->map(fn ($isbn) => $this->cleanIsbn($isbn))
            ->filter()
            ->values();

        $isbn13 = $cleaned->first(fn ($isbn) => strlen($isbn) === 13);

        if ($isbn13) {
            return $isbn13;
        }

        return $cleaned->first() ?? '';
    }

    /**
     * Cleans ISBN input by removing invalid characters and limiting it to 13 characters.
     */
    private function cleanIsbn(?string $isbn): ?string
    {
        if (!$isbn) {
            return null;
        }

        $isbn = strtoupper(preg_replace('/[^0-9Xx]/', '', $isbn));

        if ($isbn === '') {
            return null;
        }

        return substr($isbn, 0, 13);
    }

    /**
     * Converts Google Books dates into YYYY-MM-DD format for database storage.
     */
    private function normalizePublishedDate(?string $publishedDate): ?string
    {
        if (!$publishedDate) {
            return null;
        }

        $publishedDate = trim($publishedDate);

        if (preg_match('/^\d{4}$/', $publishedDate)) {
            return $publishedDate . '-01-01';
        }

        if (preg_match('/^\d{4}-\d{2}$/', $publishedDate)) {
            return $publishedDate . '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $publishedDate)) {
            return $publishedDate;
        }

        return null;
    }

    /**
     * Converts a publication year into a full YYYY-MM-DD date.
     */
    private function normalizePublishedYear(null|int|string $year): ?string
    {
        if (!$year) {
            return null;
        }

        $year = trim((string) $year);

        if (preg_match('/^\d{4}$/', $year)) {
            return $year . '-01-01';
        }

        return null;
    }

    /**
     * Extracts a year from Open Library publish_date values and converts it to YYYY-MM-DD.
     */
    private function normalizePublishedDateFromOpenLibrary(?string $publishDate): ?string
    {
        if (!$publishDate) {
            return null;
        }

        if (preg_match('/\b(\d{4})\b/', $publishDate, $matches)) {
            return $matches[1] . '-01-01';
        }

        return null;
    }

    /**
     * Extracts Open Library descriptions whether stored as text or an array value.
     */
    private function extractOpenLibraryDescription(mixed $description): string
    {
        if (is_string($description)) {
            return $description;
        }

        if (is_array($description) && isset($description['value'])) {
            return $description['value'];
        }

        return '';
    }
}