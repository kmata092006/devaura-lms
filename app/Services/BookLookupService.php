<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BookLookupService
{
    private const CACHE_VERSION = 'v7';
    private const CACHE_HOURS = 8;
    private const RESULT_LIMIT = 12;
    private const PROVIDER_RESULT_LIMIT = 20;

    /**
     * Searches Google Books and Open Library, enriches matching editions, and
     * returns the most relevant records with the most complete metadata first.
     */
    public function search(string $searchTerm): array
    {
        $searchTerm = $this->normalizeSearchText($searchTerm);

        if ($searchTerm === '') {
            return [
                'success' => false,
                'message' => 'Please enter an ISBN, book title, or title with author.',
                'books' => [],
            ];
        }

        $cacheKey = 'book_lookup_' . self::CACHE_VERSION . '_' . md5(mb_strtolower($searchTerm));
        $cached = Cache::get($cacheKey);

        if (is_array($cached) && !empty($cached['books'])) {
            return $cached;
        }

        $googleResult = $this->searchGoogleBooks($searchTerm);
        $openLibraryResult = $this->searchOpenLibrary($searchTerm);

        $providerBooks = array_merge(
            $googleResult['books'],
            $openLibraryResult['books']
        );

        // Open Library's Books API is edition-oriented and can fill publisher,
        // subject, cover, and alternate ISBN fields missing from search results.
        $editionBooks = $this->fetchOpenLibraryEditionMetadata(
            $providerBooks,
            $this->normalizeLookupIsbn($searchTerm)
        );

        $books = $this->mergeAndRankBooks(
            array_merge($providerBooks, $editionBooks),
            $searchTerm
        );

        if ($books === []) {
            $messages = array_values(array_filter([
                $googleResult['message'] ?? null,
                $openLibraryResult['message'] ?? null,
            ]));

            return [
                'success' => false,
                'message' => $messages !== []
                    ? implode(' ', array_unique($messages)) . ' Try an exact ISBN, the complete title, or include the author name.'
                    : 'No matching book was found. Try an exact ISBN, the complete title, or include the author name.',
                'books' => [],
            ];
        }

        $sources = [];

        if ($googleResult['available']) {
            $sources[] = 'Google Books';
        }

        if ($openLibraryResult['available'] || $editionBooks !== []) {
            $sources[] = 'Open Library';
        }

        $result = [
            'success' => true,
            'message' => 'Found ' . count($books) . ' matching ' . (count($books) === 1 ? 'book' : 'books')
                . ($sources !== [] ? ' using ' . implode(' and ', array_unique($sources)) : '')
                . '. Matching edition details were combined when available.',
            'books' => $books,
        ];

        // Do not cache provider failures or empty responses. This prevents a
        // temporary API interruption from making later searches fail for hours.
        Cache::put($cacheKey, $result, now()->addHours(self::CACHE_HOURS));

        return $result;
    }

    /**
     * Performs an exact field search first, then a broader fallback search when
     * the first request does not return enough useful matches.
     *
     * @return array{available: bool, message: string, books: array<int, array<string, mixed>>}
     */
    private function searchGoogleBooks(string $searchTerm): array
    {
        $books = [];
        $providerResponded = false;
        $queries = $this->buildGoogleQueries($searchTerm);

        foreach ($queries as $query) {
            $params = [
                'q' => $query,
                'maxResults' => self::PROVIDER_RESULT_LIMIT,
                'printType' => 'books',
                'projection' => 'full',
                'orderBy' => 'relevance',
            ];

            $apiKey = trim((string) config('services.google_books.key'));

            if ($apiKey !== '') {
                $params['key'] = $apiKey;
            }

            $response = $this->requestJson(
                'https://www.googleapis.com/books/v1/volumes',
                $params,
                'Google Books',
                $query
            );

            // A missing, expired, or restricted key should not stop public
            // catalog lookup. Retry once without the optional key.
            if ($response === null && array_key_exists('key', $params)) {
                unset($params['key']);

                $response = $this->requestJson(
                    'https://www.googleapis.com/books/v1/volumes',
                    $params,
                    'Google Books public fallback',
                    $query
                );
            }

            if ($response === null) {
                continue;
            }

            $providerResponded = true;

            foreach ($response['items'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $book = $this->normalizeGoogleBook($item);

                if ($book['book_title'] !== '') {
                    $books[] = $book;
                }
            }

            if (count($books) >= self::PROVIDER_RESULT_LIMIT) {
                break;
            }
        }

        return [
            'available' => $providerResponded,
            'message' => $providerResponded
                ? ($books === [] ? 'Google Books did not return a usable match.' : '')
                : 'Google Books is temporarily unavailable.',
            'books' => $books,
        ];
    }

    /**
     * Uses title-specific and general Open Library searches. Explicit fields
     * ensure publisher, subjects, ISBNs, dates, covers, and edition identifiers
     * are requested rather than relying on the provider's default field set.
     *
     * @return array{available: bool, message: string, books: array<int, array<string, mixed>>}
     */
    private function searchOpenLibrary(string $searchTerm): array
    {
        $books = [];
        $providerResponded = false;
        $queries = $this->buildOpenLibraryQueries($searchTerm);

        foreach ($queries as $params) {
            $params = array_merge([
                'limit' => self::PROVIDER_RESULT_LIMIT,
                'fields' => implode(',', [
                    'key',
                    'title',
                    'subtitle',
                    'author_name',
                    'isbn',
                    'publisher',
                    'subject',
                    'subject_facet',
                    'first_publish_year',
                    'publish_date',
                    'cover_i',
                    'edition_key',
                ]),
            ], $params);

            $response = $this->requestJson(
                'https://openlibrary.org/search.json',
                $params,
                'Open Library',
                $searchTerm
            );

            if ($response === null) {
                continue;
            }

            $providerResponded = true;

            foreach ($response['docs'] ?? [] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $book = $this->normalizeOpenLibraryBook($item);

                if ($book['book_title'] !== '') {
                    $books[] = $book;
                }
            }

            if (count($books) >= self::PROVIDER_RESULT_LIMIT) {
                break;
            }
        }

        return [
            'available' => $providerResponded,
            'message' => $providerResponded
                ? ($books === [] ? 'Open Library did not return a usable match.' : '')
                : 'Open Library is temporarily unavailable.',
            'books' => $books,
        ];
    }

    /**
     * Requests edition-level records in one Open Library Books API call. This
     * often supplies the publisher or ISBN that is absent from a work-level
     * title search result.
     *
     * @param array<int, array<string, mixed>> $books
     * @return array<int, array<string, mixed>>
     */
    private function fetchOpenLibraryEditionMetadata(array $books, string $searchedIsbn): array
    {
        $isbns = [];

        if ($searchedIsbn !== '') {
            $isbns[] = $searchedIsbn;
        }

        foreach ($books as $book) {
            foreach ($book['all_isbns'] ?? [] as $isbn) {
                if ($this->normalizeCatalogIsbn((string) $isbn) !== '') {
                    $isbns[] = $this->normalizeCatalogIsbn((string) $isbn);
                }
            }

            if (!empty($book['isbn'])) {
                $isbns[] = (string) $book['isbn'];
            }
        }

        $isbns = array_slice(array_values(array_unique(array_filter($isbns))), 0, 20);

        if ($isbns === []) {
            return [];
        }

        $bibKeys = implode(',', array_map(
            fn (string $isbn) => 'ISBN:' . $isbn,
            $isbns
        ));

        $response = $this->requestJson(
            'https://openlibrary.org/api/books',
            [
                'bibkeys' => $bibKeys,
                'format' => 'json',
                'jscmd' => 'data',
            ],
            'Open Library Books API',
            $searchedIsbn !== '' ? $searchedIsbn : 'edition enrichment'
        );

        if ($response === null) {
            return [];
        }

        $editionBooks = [];

        foreach ($response as $bibKey => $item) {
            if (!is_array($item)) {
                continue;
            }

            $book = $this->normalizeOpenLibraryEditionBook($item, (string) $bibKey);

            if ($book['book_title'] !== '') {
                $editionBooks[] = $book;
            }
        }

        return $editionBooks;
    }

    /**
     * Sends a resilient public metadata request. One retry handles short-lived
     * provider or network interruptions while keeping the lookup responsive.
     */
    private function requestJson(
        string $url,
        array $params,
        string $provider,
        string $query
    ): ?array {
        try {
            $response = Http::connectTimeout(5)
                ->timeout(14)
                ->retry(2, 300, null, false)
                ->withoutVerifying()
                ->acceptJson()
                ->withHeaders([
                    'User-Agent' => 'UBNHS-Library-Management-System/1.0',
                ])
                ->get($url, $params);

            if (!$response->successful()) {
                $this->logProviderFailure($provider, $query, $response);
                return null;
            }

            $data = $response->json();

            return is_array($data) ? $data : null;
        } catch (\Throwable $exception) {
            Log::warning($provider . ' lookup failed', [
                'query' => $query,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function logProviderFailure(string $provider, string $query, Response $response): void
    {
        Log::warning($provider . ' returned an unsuccessful response', [
            'query' => $query,
            'status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 1000),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function buildGoogleQueries(string $searchTerm): array
    {
        $isbn = $this->normalizeLookupIsbn($searchTerm);

        if ($isbn !== '') {
            return ['isbn:' . $isbn];
        }

        [$title, $author] = $this->splitTitleAndAuthor($searchTerm);
        $queries = [];
        $safeTitle = str_replace('"', '', $title);

        if ($safeTitle !== '') {
            $exact = 'intitle:"' . $safeTitle . '"';

            if ($author !== '') {
                $exact .= ' inauthor:"' . str_replace('"', '', $author) . '"';
            }

            $queries[] = $exact;
        }

        $queries[] = $searchTerm;

        return array_values(array_unique(array_filter($queries)));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildOpenLibraryQueries(string $searchTerm): array
    {
        $isbn = $this->normalizeLookupIsbn($searchTerm);

        if ($isbn !== '') {
            return [
                ['isbn' => $isbn],
            ];
        }

        [$title, $author] = $this->splitTitleAndAuthor($searchTerm);
        $queries = [];

        if ($title !== '') {
            $exact = ['title' => $title];

            if ($author !== '') {
                $exact['author'] = $author;
            }

            $queries[] = $exact;
        }

        $queries[] = ['q' => $searchTerm];

        return $queries;
    }

    /**
     * Accepts common input such as "Book Title by Author Name" while preserving
     * ordinary titles that do not contain an author separator.
     *
     * @return array{0: string, 1: string}
     */
    private function splitTitleAndAuthor(string $searchTerm): array
    {
        $parts = preg_split('/\s+by\s+/iu', $searchTerm, 2);

        if (is_array($parts) && count($parts) === 2) {
            return [trim($parts[0]), trim($parts[1])];
        }

        return [$searchTerm, ''];
    }

    /**
     * Converts one Google Books volume into the shared lookup structure.
     */
    private function normalizeGoogleBook(array $item): array
    {
        $volumeInfo = is_array($item['volumeInfo'] ?? null) ? $item['volumeInfo'] : [];
        $title = $this->combineTitleAndSubtitle(
            (string) ($volumeInfo['title'] ?? ''),
            (string) ($volumeInfo['subtitle'] ?? '')
        );
        $authors = $this->normalizeStringList($volumeInfo['authors'] ?? []);
        $publishers = $this->normalizeStringList($volumeInfo['publisher'] ?? []);
        $subjects = $this->normalizeStringList($volumeInfo['categories'] ?? []);
        $isbns = $this->extractGoogleIsbns($volumeInfo['industryIdentifiers'] ?? []);
        $publication = $this->extractPublicationMetadata($volumeInfo['publishedDate'] ?? null);

        return [
            'source' => 'Google Books',
            'book_title' => $title,
            'isbn' => $this->chooseBestIsbn($isbns),
            'all_isbns' => $isbns,
            'author_name' => trim((string) ($authors[0] ?? '')),
            'authors' => $authors,
            'publisher_name' => $this->chooseBestPublisher($publishers),
            'publishers' => $publishers,
            'date_published' => $publication['date_published'],
            'publication_year' => $publication['publication_year'],
            'genre_name' => $this->chooseBestGenre($subjects),
            'subjects' => $subjects,
            'description' => $this->normalizeDescription($volumeInfo['description'] ?? ''),
            'thumbnail' => $this->normalizeImageUrl(
                $volumeInfo['imageLinks']['thumbnail']
                    ?? $volumeInfo['imageLinks']['smallThumbnail']
                    ?? ''
            ),
            'preview_link' => trim((string) ($volumeInfo['previewLink'] ?? '')),
            'provider_id' => trim((string) ($item['id'] ?? '')),
        ];
    }

    /**
     * Converts one Open Library search document into the shared structure.
     */
    private function normalizeOpenLibraryBook(array $item): array
    {
        $title = $this->combineTitleAndSubtitle(
            (string) ($item['title'] ?? ''),
            (string) ($item['subtitle'] ?? '')
        );
        $authors = $this->normalizeStringList($item['author_name'] ?? []);
        $publishers = $this->normalizeStringList(
            $item['publisher'] ?? $item['publisher_facet'] ?? []
        );
        $subjects = $this->normalizeStringList(array_merge(
            $this->normalizeStringList($item['subject'] ?? []),
            $this->normalizeStringList($item['subject_facet'] ?? [])
        ));
        $isbns = $this->normalizeIsbnList($item['isbn'] ?? []);
        $publication = $this->extractOpenLibraryPublicationMetadata($item);
        $coverId = $item['cover_i'] ?? null;

        return [
            'source' => 'Open Library',
            'book_title' => $title,
            'isbn' => $this->chooseBestIsbn($isbns),
            'all_isbns' => $isbns,
            'author_name' => trim((string) ($authors[0] ?? '')),
            'authors' => $authors,
            'publisher_name' => $this->chooseBestPublisher($publishers),
            'publishers' => $publishers,
            'date_published' => $publication['date_published'],
            'publication_year' => $publication['publication_year'],
            'genre_name' => $this->chooseBestGenre($subjects),
            'subjects' => $subjects,
            'description' => '',
            'thumbnail' => $coverId
                ? 'https://covers.openlibrary.org/b/id/' . $coverId . '-M.jpg'
                : ($this->chooseBestIsbn($isbns) !== ''
                    ? 'https://covers.openlibrary.org/b/isbn/' . $this->chooseBestIsbn($isbns) . '-M.jpg'
                    : ''),
            'preview_link' => !empty($item['key'])
                ? 'https://openlibrary.org' . $item['key']
                : '',
            'provider_id' => trim((string) ($item['key'] ?? '')),
        ];
    }

    /**
     * Converts Open Library Books API edition data into the shared structure.
     */
    private function normalizeOpenLibraryEditionBook(array $item, string $bibKey): array
    {
        $authors = collect($item['authors'] ?? [])
            ->map(fn ($author) => is_array($author) ? trim((string) ($author['name'] ?? '')) : '')
            ->filter()
            ->values()
            ->all();
        $publishers = collect($item['publishers'] ?? [])
            ->map(fn ($publisher) => is_array($publisher) ? trim((string) ($publisher['name'] ?? '')) : '')
            ->filter()
            ->values()
            ->all();
        $subjects = collect($item['subjects'] ?? [])
            ->map(fn ($subject) => is_array($subject) ? trim((string) ($subject['name'] ?? '')) : '')
            ->filter()
            ->values()
            ->all();

        $identifierValues = [];

        foreach (($item['identifiers'] ?? []) as $identifierType => $values) {
            if (in_array($identifierType, ['isbn_10', 'isbn_13'], true)) {
                $identifierValues = array_merge($identifierValues, $this->normalizeStringList($values));
            }
        }

        $bibIsbn = str_starts_with($bibKey, 'ISBN:') ? substr($bibKey, 5) : '';
        $isbns = $this->normalizeIsbnList(array_merge($identifierValues, [$bibIsbn]));
        $publication = $this->extractPublicationMetadata($item['publish_date'] ?? null);
        $cover = is_array($item['cover'] ?? null) ? $item['cover'] : [];

        return [
            'source' => 'Open Library Edition',
            'book_title' => trim((string) ($item['title'] ?? '')),
            'isbn' => $this->chooseBestIsbn($isbns),
            'all_isbns' => $isbns,
            'author_name' => trim((string) ($authors[0] ?? '')),
            'authors' => $authors,
            'publisher_name' => $this->chooseBestPublisher($publishers),
            'publishers' => $publishers,
            'date_published' => $publication['date_published'],
            'publication_year' => $publication['publication_year'],
            'genre_name' => $this->chooseBestGenre($subjects),
            'subjects' => $subjects,
            'description' => '',
            'thumbnail' => $this->normalizeImageUrl(
                $cover['medium'] ?? $cover['large'] ?? $cover['small'] ?? ''
            ),
            'preview_link' => trim((string) ($item['url'] ?? '')),
            'provider_id' => $bibKey,
        ];
    }

    /**
     * Merges duplicate provider records and ranks them by query relevance and
     * metadata completeness.
     *
     * @param array<int, array<string, mixed>> $books
     * @return array<int, array<string, mixed>>
     */
    private function mergeAndRankBooks(array $books, string $searchTerm): array
    {
        $merged = [];

        foreach ($books as $book) {
            if (($book['book_title'] ?? '') === '') {
                continue;
            }

            $matchIndex = $this->findMatchingBookIndex($merged, $book);

            if ($matchIndex === null) {
                $merged[] = $book;
                continue;
            }

            $merged[$matchIndex] = $this->mergeBookMetadata($merged[$matchIndex], $book);
        }

        $merged = array_map(
            fn (array $book) => $this->finalizeBook($book, $searchTerm),
            $merged
        );

        usort($merged, function (array $left, array $right): int {
            $relevance = ($right['relevance_score'] ?? 0) <=> ($left['relevance_score'] ?? 0);

            if ($relevance !== 0) {
                return $relevance;
            }

            $metadata = ($right['metadata_score'] ?? 0) <=> ($left['metadata_score'] ?? 0);

            if ($metadata !== 0) {
                return $metadata;
            }

            return strcasecmp($left['book_title'] ?? '', $right['book_title'] ?? '');
        });

        return array_slice($merged, 0, self::RESULT_LIMIT);
    }

    private function findMatchingBookIndex(array $books, array $candidate): ?int
    {
        $candidateIsbns = $candidate['all_isbns'] ?? [];
        $candidateTitle = $this->normalizeComparisonText($candidate['book_title'] ?? '');
        $candidateAuthor = $this->normalizeComparisonText($candidate['author_name'] ?? '');

        foreach ($books as $index => $book) {
            $bookIsbns = $book['all_isbns'] ?? [];

            if (array_intersect($candidateIsbns, $bookIsbns) !== []) {
                return $index;
            }

            $bookTitle = $this->normalizeComparisonText($book['book_title'] ?? '');
            $bookAuthor = $this->normalizeComparisonText($book['author_name'] ?? '');

            if ($candidateTitle === '' || $bookTitle === '') {
                continue;
            }

            $authorsCompatible = $candidateAuthor === ''
                || $bookAuthor === ''
                || $candidateAuthor === $bookAuthor
                || str_contains($candidateAuthor, $bookAuthor)
                || str_contains($bookAuthor, $candidateAuthor);

            if (!$authorsCompatible) {
                continue;
            }

            if ($candidateTitle === $bookTitle) {
                return $index;
            }

            $shorter = min(strlen($candidateTitle), strlen($bookTitle));
            $longer = max(strlen($candidateTitle), strlen($bookTitle));
            $prefixMatch = $shorter >= 8
                && (str_starts_with($candidateTitle, $bookTitle) || str_starts_with($bookTitle, $candidateTitle))
                && ($shorter / max($longer, 1)) >= 0.72;

            similar_text($candidateTitle, $bookTitle, $similarity);

            if ($prefixMatch || $similarity >= 92.0) {
                return $index;
            }
        }

        return null;
    }

    private function mergeBookMetadata(array $primary, array $secondary): array
    {
        $primary['authors'] = $this->mergeStringLists(
            $primary['authors'] ?? [],
            $secondary['authors'] ?? []
        );
        $primary['publishers'] = $this->mergeStringLists(
            $primary['publishers'] ?? [],
            $secondary['publishers'] ?? []
        );
        $primary['subjects'] = $this->mergeStringLists(
            $primary['subjects'] ?? [],
            $secondary['subjects'] ?? []
        );
        $primary['all_isbns'] = $this->normalizeIsbnList(array_merge(
            $primary['all_isbns'] ?? [],
            $secondary['all_isbns'] ?? []
        ));

        $primary['isbn'] = $this->chooseBestIsbn($primary['all_isbns']);
        $primary['author_name'] = $primary['author_name'] ?: ($primary['authors'][0] ?? '');
        $primary['publisher_name'] = $this->chooseBestPublisher($primary['publishers']);
        $primary['genre_name'] = $this->chooseBestGenre($primary['subjects']);

        foreach ([
            'book_title',
            'date_published',
            'publication_year',
            'description',
            'thumbnail',
            'preview_link',
            'provider_id',
        ] as $field) {
            if (empty($primary[$field]) && !empty($secondary[$field])) {
                $primary[$field] = $secondary[$field];
            }
        }

        $sources = array_values(array_unique(array_filter(array_merge(
            array_map('trim', explode('+', (string) ($primary['source'] ?? ''))),
            array_map('trim', explode('+', (string) ($secondary['source'] ?? '')))
        ))));
        $primary['source'] = implode(' + ', $sources);

        return $primary;
    }

    private function finalizeBook(array $book, string $searchTerm): array
    {
        $book['isbn'] = $this->chooseBestIsbn($book['all_isbns'] ?? []);
        $book['author_name'] = trim((string) ($book['author_name'] ?? ($book['authors'][0] ?? '')));
        $book['publisher_name'] = $this->chooseBestPublisher($book['publishers'] ?? []);
        $book['genre_name'] = $this->chooseBestGenre($book['subjects'] ?? []);

        $importantFields = [
            'isbn' => 'ISBN',
            'author_name' => 'Author',
            'publisher_name' => 'Publisher',
            'genre_name' => 'Genre',
        ];
        $missingFields = [];

        foreach ($importantFields as $field => $label) {
            if (empty($book[$field])) {
                $missingFields[] = $label;
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
            'description',
        ];

        $book['metadata_score'] = collect($scoreFields)
            ->filter(fn (string $field) => !empty($book[$field]))
            ->count();
        $book['metadata_available_count'] = count($importantFields) - count($missingFields);
        $book['metadata_total_count'] = count($importantFields);
        $book['missing_fields'] = $missingFields;
        $book['is_complete'] = $missingFields === [];
        $book['relevance_score'] = $this->calculateRelevanceScore($book, $searchTerm);

        unset($book['provider_id']);

        return $book;
    }

    private function calculateRelevanceScore(array $book, string $searchTerm): int
    {
        $searchedIsbn = $this->normalizeLookupIsbn($searchTerm);
        $titleQuery = $this->normalizeComparisonText($this->splitTitleAndAuthor($searchTerm)[0]);
        $bookTitle = $this->normalizeComparisonText($book['book_title'] ?? '');
        $score = ((int) ($book['metadata_score'] ?? 0)) * 12;

        if ($searchedIsbn !== '' && in_array($searchedIsbn, $book['all_isbns'] ?? [], true)) {
            $score += 1000;
        }

        if ($titleQuery !== '' && $bookTitle !== '') {
            if ($titleQuery === $bookTitle) {
                $score += 500;
            } elseif (str_starts_with($bookTitle, $titleQuery)) {
                $score += 340;
            } elseif (str_contains($bookTitle, $titleQuery)) {
                $score += 240;
            } else {
                similar_text($titleQuery, $bookTitle, $similarity);
                $score += (int) round($similarity * 1.7);
            }
        }

        $queryText = mb_strtolower($searchTerm);
        $titleText = mb_strtolower((string) ($book['book_title'] ?? ''));
        $derivativeWords = ['study guide', 'summary', 'workbook', 'analysis', 'companion'];

        foreach ($derivativeWords as $word) {
            if (str_contains($titleText, $word) && !str_contains($queryText, $word)) {
                $score -= 180;
            }
        }

        return $score;
    }

    /**
     * Selects a controlled catalog genre from provider categories and subjects.
     * No genre is fabricated when neither provider supplies usable subjects.
     */
    private function chooseBestGenre(array $subjects): string
    {
        $subjects = $this->normalizeStringList($subjects);

        if ($subjects === []) {
            return '';
        }

        $mappings = [
            'Science Fiction' => ['science fiction', 'sci-fi', 'space opera', 'alien contact'],
            'Fantasy' => ['fantasy', 'magic', 'fantastical'],
            'Mystery & Thriller' => ['mystery', 'thriller', 'detective', 'suspense', 'crime fiction'],
            'Romance' => ['romance', 'love stories'],
            'Biography & Autobiography' => ['biography', 'autobiography', 'memoir'],
            'History' => ['history', 'historical'],
            'Technology & Computing' => ['technology', 'computer', 'computing', 'programming', 'software'],
            'Science' => ['science', 'physics', 'chemistry', 'biology', 'astronomy'],
            'Reference' => ['reference', 'encyclopedia', 'dictionary', 'handbook'],
            'Young Adult' => ['young adult', 'juvenile fiction', 'teen fiction'],
            "Children's Literature" => ['children', 'juvenile literature', 'picture book'],
            'Poetry' => ['poetry', 'poems'],
            'Drama' => ['drama', 'plays', 'theater'],
            'Religion' => ['religion', 'religious', 'theology'],
            'Self-Help' => ['self-help', 'self help', 'personal development'],
            'Education' => ['education', 'teaching', 'study and teaching'],
            'Nonfiction' => ['nonfiction', 'non-fiction', 'essays'],
            'Fiction' => ['fiction', 'novel', 'literary fiction'],
        ];

        foreach ($subjects as $subject) {
            $normalized = mb_strtolower($subject);

            foreach ($mappings as $genre => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($normalized, $keyword)) {
                        return $genre;
                    }
                }
            }
        }

        $ignored = [
            'accessible book',
            'protected daisy',
            'large type books',
            'reading level',
            'nyt bestseller',
        ];

        foreach ($subjects as $subject) {
            $normalized = mb_strtolower($subject);
            $isIgnored = collect($ignored)->contains(
                fn (string $item) => str_contains($normalized, $item)
            );

            if (!$isIgnored && mb_strlen($subject) <= 100) {
                return $subject;
            }
        }

        return '';
    }

    private function chooseBestPublisher(array $publishers): string
    {
        $publishers = $this->normalizeStringList($publishers);

        if ($publishers === []) {
            return '';
        }

        usort($publishers, function (string $left, string $right): int {
            $leftScore = $this->publisherQualityScore($left);
            $rightScore = $this->publisherQualityScore($right);

            return $rightScore <=> $leftScore;
        });

        return $publishers[0] ?? '';
    }

    private function publisherQualityScore(string $publisher): int
    {
        $normalized = mb_strtolower(trim($publisher));
        $score = 100;

        if ($normalized === '' || in_array($normalized, ['unknown', 'n/a', 'not available'], true)) {
            return 0;
        }

        if (mb_strlen($publisher) > 120) {
            $score -= 40;
        }

        if (preg_match('/\b(press|books|publishing|publisher|house|inc\.?|ltd\.?)\b/i', $publisher)) {
            $score += 20;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function extractGoogleIsbns(array $identifiers): array
    {
        $values = [];

        foreach ($identifiers as $identifier) {
            if (!is_array($identifier)) {
                continue;
            }

            $type = strtoupper(trim((string) ($identifier['type'] ?? '')));

            if (in_array($type, ['ISBN_10', 'ISBN_13'], true)) {
                $values[] = (string) ($identifier['identifier'] ?? '');
            }
        }

        return $this->normalizeIsbnList($values);
    }

    private function normalizeLookupIsbn(?string $isbn): string
    {
        $value = strtoupper(preg_replace('/[^0-9X]/i', '', trim((string) $isbn)) ?? '');

        if (strlen($value) === 13 && preg_match('/^\d{13}$/', $value) && $this->isValidIsbn13($value)) {
            return $value;
        }

        if (strlen($value) === 10 && preg_match('/^\d{9}[\dX]$/', $value) && $this->isValidIsbn10($value)) {
            return $value;
        }

        return '';
    }

    private function normalizeCatalogIsbn(?string $isbn): string
    {
        $digits = preg_replace('/\D+/', '', trim((string) $isbn)) ?? '';

        if (strlen($digits) === 13 && $this->isValidIsbn13($digits)) {
            return $digits;
        }

        if (strlen($digits) === 10 && $this->isValidIsbn10($digits)) {
            return $digits;
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function normalizeIsbnList(mixed $isbns): array
    {
        $values = is_array($isbns) ? $isbns : [$isbns];
        $normalized = [];

        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $lookupIsbn = $this->normalizeLookupIsbn((string) $value);

            if ($lookupIsbn === '') {
                continue;
            }

            if (strlen($lookupIsbn) === 13) {
                $normalized[] = $lookupIsbn;
                continue;
            }

            // The local catalog accepts numeric ISBN values only. Convert every
            // valid ISBN-10, including those ending in X, to numeric ISBN-13.
            $isbn13 = $this->convertIsbn10To13($lookupIsbn);

            if ($isbn13 !== '') {
                $normalized[] = $isbn13;
            }

            if (preg_match('/^\d{10}$/', $lookupIsbn)) {
                $normalized[] = $lookupIsbn;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function convertIsbn10To13(string $isbn10): string
    {
        if (!$this->isValidIsbn10($isbn10)) {
            return '';
        }

        $base = '978' . substr($isbn10, 0, 9);
        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $sum += ((int) $base[$index]) * ($index % 2 === 0 ? 1 : 3);
        }

        return $base . ((10 - ($sum % 10)) % 10);
    }

    private function chooseBestIsbn(array $isbns): string
    {
        $normalized = $this->normalizeIsbnList($isbns);

        foreach ($normalized as $isbn) {
            if (strlen($isbn) === 13) {
                return $isbn;
            }
        }

        foreach ($normalized as $isbn) {
            if (strlen($isbn) === 10) {
                return $isbn;
            }
        }

        return '';
    }

    private function isValidIsbn10(string $isbn): bool
    {
        if (!preg_match('/^\d{9}[\dX]$/', $isbn)) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 10; $index++) {
            $digit = $isbn[$index] === 'X' ? 10 : (int) $isbn[$index];
            $sum += $digit * (10 - $index);
        }

        return $sum % 11 === 0;
    }

    private function isValidIsbn13(string $isbn): bool
    {
        if (!preg_match('/^\d{13}$/', $isbn)) {
            return false;
        }

        $sum = 0;

        for ($index = 0; $index < 12; $index++) {
            $sum += ((int) $isbn[$index]) * ($index % 2 === 0 ? 1 : 3);
        }

        return ((10 - ($sum % 10)) % 10) === (int) $isbn[12];
    }

    /**
     * Separates exact dates from year-only values without inventing January 1.
     *
     * @return array{date_published: ?string, publication_year: ?string}
     */
    private function extractPublicationMetadata(?string $publishedDate): array
    {
        $publishedDate = trim((string) $publishedDate);

        if ($publishedDate === '') {
            return ['date_published' => null, 'publication_year' => null];
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
            'publication_year' => $exactDate !== null ? substr($exactDate, 0, 4) : $year,
        ];
    }

    /**
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

    private function combineTitleAndSubtitle(string $title, string $subtitle): string
    {
        $title = $this->normalizeSearchText($title);
        $subtitle = $this->normalizeSearchText($subtitle);

        if ($subtitle !== '' && !str_contains(mb_strtolower($title), mb_strtolower($subtitle))) {
            $title .= ': ' . $subtitle;
        }

        return trim($title);
    }

    /**
     * Converts provider values that may be a string or array into a clean list.
     *
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (is_string($value) || is_numeric($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($item) => is_scalar($item) ? $this->normalizeSearchText((string) $item) : '',
            $value
        ), fn (string $item) => $item !== '')));
    }

    private function mergeStringLists(array $left, array $right): array
    {
        return $this->normalizeStringList(array_merge($left, $right));
    }

    private function normalizeDescription(mixed $description): string
    {
        if (is_array($description)) {
            $description = $description['value'] ?? '';
        }

        return is_scalar($description)
            ? trim(strip_tags((string) $description))
            : '';
    }

    private function normalizeImageUrl(?string $url): string
    {
        $url = trim((string) $url);

        return str_starts_with($url, 'http://')
            ? 'https://' . substr($url, 7)
            : $url;
    }

    private function normalizeSearchText(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function normalizeComparisonText(string $value): string
    {
        $value = mb_strtolower($this->normalizeSearchText($value));

        return preg_replace('/[^\pL\pN]+/u', '', $value) ?? '';
    }
}
