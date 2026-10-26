<?php

namespace App\Services\SheetSync;

use Generator;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SheetApiClient
{
    protected string $baseUrl;

    protected string $token;

    protected int $timeout;

    protected int $limit;

    protected int $maxRetries = 3;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('sheetsync.base_url'), '/');
        $this->token   = (string) config('sheetsync.token');
        $this->timeout = (int) config('sheetsync.timeout', 120);
        $this->limit   = (int) config('sheetsync.limit', 500);
    }

    /**
     * Fetch a single page from the API.
     *
     * @throws SheetApiException
     */
    public function fetch(string $resource, array $query = []): array
    {
        $query = array_merge([
            'token'    => $this->token,
            'resource' => $resource,
            'limit'    => $this->limit,
        ], $query);

        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = Http::timeout($this->timeout)
                    ->withOptions(['allow_redirects' => true])
                    ->get($this->baseUrl, $query);

                $body = $response->body();

                if (empty($body)) {
                    throw new SheetApiException("Empty response body for resource: {$resource}");
                }

                $data = json_decode($body, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new SheetApiException("Invalid JSON response for resource {$resource}: " . json_last_error_msg());
                }

                if (! isset($data['ok']) || $data['ok'] !== true) {
                    $errorMsg = $data['error'] ?? 'unknown error';
                    $status   = $data['status'] ?? 0;
                    throw new SheetApiException("API error for resource {$resource}: {$errorMsg} (status: {$status})");
                }

                Log::channel('sheet-sync')->info('Page fetched', [
                    'resource' => $resource,
                    'page'     => $data['page'] ?? 0,
                    'count'    => $data['count'] ?? 0,
                    'total'    => $data['total'] ?? 0,
                ]);

                return $data;
            } catch (SheetApiException $e) {
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }

                Log::channel('sheet-sync')->warning("Retry {$attempt}/{$this->maxRetries} for resource {$resource}", [
                    'error' => $e->getMessage(),
                ]);

                sleep($attempt * 2);
            } catch (RequestException | Throwable $e) {
                if ($attempt >= $this->maxRetries) {
                    throw new SheetApiException(
                        "HTTP request failed for resource {$resource} after {$this->maxRetries} attempts: " . $e->getMessage(),
                        0,
                        $e
                    );
                }

                Log::channel('sheet-sync')->warning("Retry {$attempt}/{$this->maxRetries} for resource {$resource}", [
                    'error' => $e->getMessage(),
                ]);

                sleep($attempt * 2);
            }
        }
    }

    /**
     * Yield pages for a resource, handling pagination via has_more.
     *
     * @return Generator<array>
     */
    public function pages(string $resource, ?string $since = null, ?int $limit = null): Generator
    {
        $page  = 1;
        $limit = $limit ?? $this->limit;

        do {
            $query = [
                'resource' => $resource,
                'page'     => $page,
                'limit'    => $limit,
            ];

            if ($since !== null) {
                $query['since'] = $since;
            }

            $data = $this->fetch($resource, $query);

            yield $data;

            $hasMore = $data['has_more'] ?? false;
            $page++;
        } while ($hasMore);
    }

    /**
     * Get the list of available resources.
     *
     * @throws SheetApiException
     */
    public function resources(): array
    {
        $data = $this->fetch('');

        return $data['data'] ?? [];
    }
}