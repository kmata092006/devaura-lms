<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookLookupService
{
    /**
     * Searches supported external book providers, combines matching records,
     * and ranks the most complete results first.
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

        // Version the key so older incomplete cached results are not reused.
        $cacheKey = 'book_lookup_v4_' . md5(mb_strtolower($searchTerm));

        return Cache::remember($cacheKey, now()->addHours(12), function () use ($searchTerm) {
            $googleResult = $this->searchGoogleBooks($searchTerm);
            $openLibraryResult = $this->searchOpenLibrary($searchTerm);

            $books = $this->mergeAndRankBooks(
                $googleResult['books'] ?? [],
                $openLibraryResult['books'] ?? []
            );

            if ($books === []) {
                return [
                    'success' => false,
                    'message' => 'No usable book details were found. Try an exact ISBN or add the book manually.',
                    'books' => [],
                ];
            }

            $sources = [];

            if (!empty($googleResult['books'])) {
                $sources[] = 'Google Books';
            }

            if (!empty($openLibraryResult['books'])) {
                $sources[] = 'Open Library';
            }

            return [
                'success' => true,
                'message' => 'Book details found using ' . implode(' and ', $sources) . '. Results with more complete metadata are shown first.',
                'books' => $books,
            ];
        });
    }

    /**
     * Searches Google Books using the full projection so the response includes
     * every available author, publisher, category, identifier, and date field.
     */
    private function searchGoogleBooks(string $searchTerm): array
    {
        $query = $this->buildGoogleQuery($searchTerm);

        try {
            $params = [
                'q' => $query,
                'maxResults' => 10,
                'printType' => 'books',
                'projection' => 'full',
                'orderBy' => 'relevance',
            ];

            $apiKey = config('services.google_books.key');

            if (!empty($apiKey)) {
                $params['key'] = $apiKey;
            }

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get('https://www.googleapis.com/books/v1/volumes', $params);

            if (!$response->successful()) {
                Log::warning('Google Books API unsuccessful response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                    'has_api_key' => !empty($apiKey),
                ]);

                return [
                    'success' => false,
                    'message' => 'Google Books lookup is currently unavailable.',
                    'books' => [],
                ];
            }

            $data = $response->json();

            $books = collect($data['items'] ?? [])
                ->map(fn (array $item) => $this->normalizeGoogleBook($item))
                ->filter(fn (array $book) => $book['book_title'] !== '')
                ->values()
                ->all();

            return [
                'success' => $books !== [],
                'message' => $books !== []
                    ? 'Book details found using Google Books.'
                    : 'No usable Google Books details were found.',
                'books' => $books,
            ];
        } catch (\Throwable $exception) {
            Log::error('Google Books lookup failed', [
                'message' => $exception->getMessage(),
                'query' => $query,
            ]);

            return [
                'success' => false,
                'message' => 'Google Books lookup failed.',
                'books' => [],
            ];
        }
    }

    /**
     * Searches Open Library as a complementary source. Its data is used both
     * for fallback results and to fill blank metadata in matching Google books.
     */
    private function searchOpenLibrary(string $searchTerm): array
    {
        $isbn = $this->normalizeExternalIsbn($searchTerm);

        try {
            $params = [
                'limit' => 10,
            ];

            if ($isbn !== '') {
                $params['isbn'] = $isbn;
            } else {
                $params['q'] = $searchTerm;
            }

            $response = Http::timeout(15)
                ->withoutVerifying()
                ->acceptJson()
                ->get('https://openlibrary.org/search.json', $params);

            if (!$response->successful()) {
                Log::warning('Open Library search unsuccessful', [
                    'status' => $response->status(),
                    'query' => $searchTerm,
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Open Library lookup is currently unavailable.',
                    'books' => [],
                ];
            }

            $data = $response->json();

            $books = collect($data['docs'] ?? [])
                ->map(fn (array $item) => $this->normalizeOpenLibraryBook($item))
                ->filter(fn (array $book) => $book['book_title'] !== '')
                ->values()
                ->all();

            return [
                'success' => $books !== [],
                'message' => $books !== []
                    ? 'Book details found using Open Library.'
                    : 'No usable Open Library details were found.',
                'books' => $books,
            ];
        } catch (\Throwable $exception) {
            Log::error('Open Library lookup failed', [
                'message' => $exception->getMessage(),
                'query' => $searchTerm,
            ]);

            return [
                'success' => false,
                'message' => 'Open Library lookup failed.',
                'books' => [],
            ];
        }
    }

    /**
     * Builds an ISBN-specific Google query when the entered value is a valid
     * numeric ISBN-10 or ISBN-13; otherwise the original title query is used.
     */
    private function buildGoogleQuery(string $searchTerm): string
    {
        $isbn = $this->normalizeExternalIsbn($searchTerm);

        return $isbn !== '' ? 'isbn:' . $isbn : $searchTerm;
    }

    /**
     * Converts one Google Books result into the system's shared lookup format.
     */
    private function normalizeGoogleBook(array $item): array
    {
        $volumeInfo = $item['volumeInfo'] ?? [];
        $title = trim((string) ($volumeInfo['title'] ?? ''));
        $subtitle = trim((string) ($volumeInfo['subtitle'] ?? ''));

        if ($subtitle !== '' && !str_contains(mb_strtolower($title), mb_strtolower($subtitle))) {
            $title .= ': ' . $subtitle;
        }

        $authors = $this->normalizeStringList($volumeInfo['authors'] ?? []);
        $categories = $this->normalizeStringList($volumeInfo['categories'] ?? []);
        $publication = $this->extractPublicationMetadata($volumeInfo['publishedDate'] ?? null);

        return [
            'source' => 'Google Books',
            'book_title' => trim($title),
            'isbn' => $this->extractGoogleIsbn($volumeInfo['industryIdentifiers'] ?? []),
            'author_name' => trim((string) ($authors[0] ?? '')),
            'authors' => $authors,
            'publisher_name' => trim((string) ($volumeInfo['publisher'] ?? '')),
            'date_published' => $publication['date_published'],
            'publication_year' => $publication['publication_year'],
            'genre_name' => trim((string) ($categories[0] ?? '')),
            'description' => trim((string) ($volumeInfo['description'] ?? '')),
            'thumbnail' => $this->normalizeImageUrl($volumeInfo['imageLinks']['thumbnail'] ?? ''),
            'preview_link' => trim((string) ($volumeInfo['previewLink'] ?? '')),
        ];
    }

    /**
     * Converts one Open Library search result into the system's shared format.
     */
    private function normalizeOpenLibraryBook(array $item): array
    {
        $title = trim((string) ($item['title'] ?? ''));
        $subtitle = trim((string) ($item['subtitle'] ?? ''));

        if ($subtitle !== '' && !str_contains(mb_strtolower($title), mb_strtolower($subtitle))) {
            $title .= ': ' . $subtitle;
        }

        $authors = $this->normalizeStringList($item['author_name'] ?? []);
        $publishers = $this->normalizeStringList($item['publisher'] ?? []);
        $subjects = $this->normalizeStringList($item['subject'] ?? []);
        $publication = $this->extractOpenLibraryPublicationMetadata($item);

        $isbn = $this->chooseBestIsbn($item['isbn'] ?? []);
        $coverId = $item['cover_i'] ?? null;

        return [
            'source' => 'Open Library',
            'book_title' => $title,
            'isbn' => $isbn,
            'author_name' => trim((string) ($authors[0] ?? '')),
            'authors' => $authors,
            'publisher_name' => trim((string) ($publishers[0] ?? '')),
            'date_published' => $publication['date_published'],
            'publication_year' => $publication['publication_year'],
            'genre_name' => trim((string) ($subjects[0] ?? '')),
            'description' => '',
            'thumbnail' => $coverId
                ? 'https://covers.openlibrary.org/b/id/' . $coverId . '-M.jpg'
                : ($isbn !== '' ? 'https://covers.openlibrary.org/b/isbn/' . $isbn . '-M.jpg' : ''),
            'preview_link' => !empty($item['key'])
                ? 'https://openlibrary.org' . $item['key']
                : '',
        ];
    }

    /**
     * Merges matching records from both providers, fills blank values, removes
     * duplicates, and places the most complete records first.
     */
    private function mergeAndRankBooks(array $googleBooks, array $openLibraryBooks): array
    {
        $merged = [];

        foreach (array_merge($googleBooks, $openLibraryBooks) as $book) {
            $matchIndex = $this->findMatchingBookIndex($merged, $book);

            if ($matchIndex === null) {
                $merged[] = $book;
                continue;
            }

            $merged[$matchIndex] = $this->mergeBookMetadata($merged[$matchIndex], $book);
        }

        $merged = array_map(fn (array $book) => $this->finalizeBook($book), $merged);

        usort($merged, function (array $left, array $right): int {
            $scoreComparison = ($right['metadata_score'] ?? 0) <=> ($left['metadata_score'] ?? 0);

            if ($scoreComparison !== 0) {
                return $scoreComparison;
            }

            return strcasecmp($left['book_title'] ?? '', $right['book_title'] ?? '');
        });

        return array_slice($merged, 0, 10);
    }

    /**
     * Locates a duplicate by ISBN first, then by normalized title and author.
     */
    private function findMatchingBookIndex(array $books, array $candidate): ?int
    {
        $candidateIsbn = $candidate['isbn'] ?? '';
        $candidateTitle = $this->normalizeComparisonText($candidate['book_title'] ?? '');
        $candidateAuthor = $this->normalizeComparisonText($candidate['author_name'] ?? '');

        foreach ($books as $index => $book) {
            $bookIsbn = $book['isbn'] ?? '';

            if ($candidateIsbn !== '' && $bookIsbn !== '' && $candidateIsbn === $bookIsbn) {
                return $index;
            }

            $bookTitle = $this->normalizeComparisonText($book['book_title'] ?? '');
            $bookAuthor = $this->normalizeComparisonText($book['author_name'] ?? '');

            if ($candidateTitle !== '' && $candidateTitle === $bookTitle) {
                if ($candidateAuthor === '' || $bookAuthor === '' || $candidateAuthor === $bookAuthor) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Keeps the primary record while filling any blank fields from its match.
     */
    private function mergeBookMetadata(array $primary, array $secondary): array
    {
        $fields = [
            'book_title',
            'isbn',
            'author_name',
            'publisher_name',
            'date_published',
            'publication_year',
            'genre_name',
            'description',
            'thumbnail',
            'preview_link',
        ];

        foreach ($fields as $field) {
            if (empty($primary[$field]) && !empty($secondary[$field])) {
                $primary[$field] = $secondary[$field];
            }
        }

        $primary['authors'] = array_values(array_unique(array_filter(array_merge(
            $primary['authors'] ?? [],
            $secondary['authors'] ?? []
        ))));

        if (($primary['source'] ?? '') !== ($secondary['source'] ?? '')) {
            $primary['source'] = 'Google Books + Open Library';
        }

        return $primary;
    }

    /**
     * Adds completeness information for the interface. Genre is the only
     * required catalog detail in this form; ISBN, author, publisher, and
     * publication date are optional when that information is unavailable.
     */
    private function finalizeBook(array $book): array
    {
        $requiredMetadata = [
            'genre_name' => 'Genre',
        ];

        $optionalMetadata = [
            'isbn' => 'ISBN',
            'author_name' => 'Author',
            'publisher_name' => 'Publisher',
        ];

        $missingFields = [];
        $optionalMissingFields = [];

        foreach ($requiredMetadata as $field => $label) {
            if (empty($book[$field])) {
                $missingFields[] = $label;
            }
        }

        foreach ($optionalMetadata as $field => $label) {
            if (empty($book[$field])) {
                $optionalMissingFields[] = $label;
            }
        }

        $scoreFields = [
            'book_title',
            'isbn',
            'author_name',
            'publisher_name',
            'genre_name',
            'date_published',
            'publication_year',
            'thumbnail',
        ];

        $book['metadata_score'] = collect($scoreFields)
            ->filter(fn (string $field) => !empty($book[$field]))
            ->count();
        $book['missing_fields'] = $missingFields;
        $book['optional_missing_fields'] = $optionalMissingFields;
        $book['is_complete'] = $missingFields === [];

        return $book;
    }

    /**
     * Extracts a numeric ISBN, preferring ISBN-13 over ISBN-10.
     */
    private function extractGoogleIsbn(array $identifiers): string
    {
        $isbn13 = '';
        $isbn10 = '';

        foreach ($identifiers as $identifier) {
            $type = $identifier['type'] ?? '';
            $value = $this->normalizeExternalIsbn($identifier['identifier'] ?? '');

            if ($type === 'ISBN_13' && strlen($value) === 13) {
                $isbn13 = $value;
            }

            if ($type === 'ISBN_10' && strlen($value) === 10) {
                $isbn10 = $value;
            }
        }

        return $isbn13 !== '' ? $isbn13 : $isbn10;
    }

    /**
     * Chooses a numeric ISBN-13 when possible, otherwise a numeric ISBN-10.
     */
    private function chooseBestIsbn(array $isbns): string
    {
        $normalized = collect($isbns)
            ->map(fn ($isbn) => $this->normalizeExternalIsbn((string) $isbn))
            ->filter()
            ->values();

        return $normalized->first(fn ($isbn) => strlen($isbn) === 13)
            ?? $normalized->first(fn ($isbn) => strlen($isbn) === 10)
            ?? '';
    }

    /**
     * Normalizes external API ISBNs into the numeric format required by the
     * local system and rejects every length other than exactly 10 or 13.
     */
    private function normalizeExternalIsbn(?string $isbn): string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $isbn));

        return $this->isValidIsbn($digits) ? $digits : '';
    }

    /**
     * Separates an exact publication date from a year-only or month-only API
     * value. Partial dates are never converted into an invented January 1 date.
     *
     * @return array{date_published: ?string, publication_year: ?string}
     */
    private function extractPublicationMetadata(?string $publishedDate): array
    {
        $publishedDate = trim((string) $publishedDate);

        if ($publishedDate === '') {
            return [
                'date_published' => null,
                'publication_year' => null,
            ];
        }

        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $publishedDate, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return [
                    'date_published' => $publishedDate,
                    'publication_year' => (string) $year,
                ];
            }
        }

        if (preg_match('/^(\d{4})(?:-\d{2})?$/', $publishedDate, $matches)) {
            return [
                'date_published' => null,
                'publication_year' => $matches[1],
            ];
        }

        $exactDate = $this->parseExactTextDate($publishedDate);
        $year = preg_match('/\b(\d{4})\b/', $publishedDate, $matches)
            ? $matches[1]
            : null;

        return [
            'date_published' => $exactDate,
            'publication_year' => $exactDate !== null
                ? substr($exactDate, 0, 4)
                : $year,
        ];
    }

    /**
     * Uses the most specific Open Library publication value available without
     * fabricating a month or day when only the publication year is known.
     *
     * @return array{date_published: ?string, publication_year: ?string}
     */
    private function extractOpenLibraryPublicationMetadata(array $item): array
    {
        $publicationYear = null;

        foreach ($this->normalizeStringList($item['publish_date'] ?? []) as $publishDate) {
            $publication = $this->extractPublicationMetadata($publishDate);

            if ($publication['date_published'] !== null) {
                return $publication;
            }

            $publicationYear ??= $publication['publication_year'];
        }

        $firstPublishYear = trim((string) ($item['first_publish_year'] ?? ''));

        if ($publicationYear === null && preg_match('/^\d{4}$/', $firstPublishYear)) {
            $publicationYear = $firstPublishYear;
        }

        return [
            'date_published' => null,
            'publication_year' => $publicationYear,
        ];
    }

    /**
     * Parses human-readable dates only when the source contains a day, a month,
     * and a four-digit year. Year-only values are intentionally not parsed.
     */
    private function parseExactTextDate(string $value): ?string
    {
        $containsYear = preg_match('/\b\d{4}\b/', $value) === 1;
        $containsDay = preg_match('/\b(?:0?[1-9]|[12]\d|3[01])(?:st|nd|rd|th)?\b/i', $value) === 1;
        $containsMonth = preg_match(
            '/\b(?:January|February|March|April|May|June|July|August|September|October|November|December|Jan|Feb|Mar|Apr|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec)\b/i',
            $value
        ) === 1;

        if (!$containsYear || !$containsDay || !$containsMonth) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * Validates numeric ISBN-10 and ISBN-13 check digits. ISBN-10 values ending
     * in X are excluded because the local catalog requires numeric-only input.
     */
    private function isValidIsbn(string $isbn): bool
    {
        if (!preg_match('/^(?:[0-9]{10}|[0-9]{13})$/', $isbn)) {
            return false;
        }

        if (strlen($isbn) === 10) {
            $sum = 0;

            for ($index = 0; $index < 10; $index++) {
                $sum += ((int) $isbn[$index]) * (10 - $index);
            }

            return $sum % 11 === 0;
        }

        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $weight = $index % 2 === 0 ? 1 : 3;
            $sum += ((int) $isbn[$index]) * $weight;
        }

        $expectedCheckDigit = (10 - ($sum % 10)) % 10;

        return $expectedCheckDigit === (int) $isbn[12];
    }


    /**
     * Converts API values that may be a string or array into a clean list.
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => is_scalar($item) ? trim((string) $item) : '',
            $value
        ), fn (string $item) => $item !== ''));
    }

    /**
     * Normalizes image links so browser pages do not contain mixed-content URLs.
     */
    private function normalizeImageUrl(?string $url): string
    {
        $url = trim((string) $url);

        return str_starts_with($url, 'http://')
            ? 'https://' . substr($url, 7)
            : $url;
    }

    /**
     * Produces a stable comparison value for duplicate detection.
     */
    private function normalizeComparisonText(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return preg_replace('/[^\pL\pN]+/u', '', $value) ?? '';
    }
}
