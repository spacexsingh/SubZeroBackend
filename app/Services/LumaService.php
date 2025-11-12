<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LumaService
{
    /**
     * The base URL for the Luma API.
     */
    protected string $baseUrl;

    /**
     * The API version.
     */
    protected string $apiVersion;

    /**
     * The request timeout in seconds.
     */
    protected int $timeout;

    /**
     * Create a new Luma service instance.
     */
    public function __construct()
    {
        $this->baseUrl = config('luma.api_url');
        $this->apiVersion = config('luma.api_version');
        $this->timeout = config('luma.timeout');
    }

    /**
     * Get the HTTP client with common configuration.
     */
    protected function client(string $apiKey, bool $withRetry = false): PendingRequest
    {
        $client = Http::withHeaders([
            'x-luma-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])
        ->timeout($this->timeout);

        // Only add retry logic if requested (for background jobs)
        if ($withRetry) {
            $client = $client->retry(
                config('luma.retry.times'),
                config('luma.retry.sleep'),
                function ($exception, $request) {
                    // Only retry on server errors (5xx), not client errors (4xx)
                    return $exception instanceof \Illuminate\Http\Client\RequestException
                        && $exception->response->status() >= 500;
                },
                throw: false
            );
        }

        return $client;
    }

    /**
     * Build the full API endpoint URL.
     */
    protected function endpoint(string $path): string
    {
        return "{$this->baseUrl}/{$this->apiVersion}/{$path}";
    }

    /**
     * Get guests for a specific event.
     *
     * @param string $eventApiId The Luma event API ID
     * @param string $apiKey The Luma API key
     * @param string|null $paginationCursor The cursor for pagination
     * @param bool $withRetry Whether to retry on failures (default false for HTTP requests)
     * @return array The API response
     * @throws \Exception
     */
    public function getGuests(string $eventApiId, string $apiKey, ?string $paginationCursor = null, bool $withRetry = false): array
    {
        $queryParams = [
            'event_api_id' => $eventApiId,
        ];

        if ($paginationCursor) {
            $queryParams['pagination_cursor'] = $paginationCursor;
        }

        try {
            $response = $this->client($apiKey, $withRetry)->get(
                $this->endpoint('event/get-guests'),
                $queryParams
            );

            if (!$response->successful()) {
                $this->logError('get-guests', $response, [
                    'event_api_id' => $eventApiId,
                    'cursor' => $paginationCursor,
                ]);

                throw new \Exception(
                    "Luma API error: {$response->status()} - {$response->body()}"
                );
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Luma API connection error', [
                'endpoint' => 'get-guests',
                'event_api_id' => $eventApiId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to connect to Luma API: {$e->getMessage()}");
        }
    }

    /**
     * Get event details.
     *
     * @param string $eventApiId The Luma event API ID
     * @param string $apiKey The Luma API key
     * @return array The API response
     * @throws \Exception
     */
    public function getEvent(string $eventApiId, string $apiKey): array
    {
        try {
            $response = $this->client($apiKey)->get(
                $this->endpoint('event/get'),
                ['event_api_id' => $eventApiId]
            );

            if (!$response->successful()) {
                $this->logError('get-event', $response, [
                    'event_api_id' => $eventApiId,
                ]);

                throw new \Exception(
                    "Luma API error: {$response->status()} - {$response->body()}"
                );
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Luma API connection error', [
                'endpoint' => 'get-event',
                'event_api_id' => $eventApiId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to connect to Luma API: {$e->getMessage()}");
        }
    }

    /**
     * Get a single guest by guest ID.
     *
     * @param string $guestApiId The Luma guest API ID
     * @param string $eventApiId The Luma event API ID
     * @param string $apiKey The Luma API key
     * @param bool $withRetry Whether to retry on failures (default false for HTTP requests)
     * @return array The guest data
     * @throws \Exception
     */
    public function getGuestById(string $guestApiId, string $eventApiId, string $apiKey, bool $withRetry = false): array
    {
        try {
            $response = $this->client($apiKey, $withRetry)->get(
                $this->endpoint('event/get-guest'),
                [
                    'id' => $guestApiId,
                    'event_id' => $eventApiId,
                ]
            );

            if (!$response->successful()) {
                $this->logError('get-guest', $response, [
                    'guest_id' => $guestApiId,
                    'event_id' => $eventApiId,
                ]);

                throw new \Exception(
                    "Luma API error: {$response->status()} - {$response->body()}"
                );
            }

            return $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Luma API connection error', [
                'endpoint' => 'event/get-guest',
                'guest_id' => $guestApiId,
                'event_id' => $eventApiId,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Failed to connect to Luma API: {$e->getMessage()}");
        }
    }

    /**
     * Log API errors.
     */
    protected function logError(string $endpoint, Response $response, array $context = []): void
    {
        Log::error('Luma API request failed', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'body' => $response->body(),
            'context' => $context,
        ]);
    }
}