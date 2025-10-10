<?php

namespace App\Services;

use App\Repositories\EventRepository;
use App\Services\ProviderSync\EventDataTransformer;
use App\Services\ProviderSync\ProviderApiClient;
use App\Services\ProviderSync\XmlEventParser;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProviderSyncService
{
    const CIRCUIT_BREAKER_KEY = 'provider-circuit-breaker';

    const BATCH_SIZE = 200;

    public function __construct(
        protected ProviderApiClient $apiClient,
        protected XmlEventParser $parser,
        protected EventDataTransformer $transformer,
        protected EventRepository $repository
    ) {}

    protected function processAndPersistEvents(string $filePath): int
    {
        $allEvents = [];
        foreach ($this->parser->parse($filePath) as $nodes) {
            $eventData = $this->transformer->transform($nodes['plan_node'], $nodes['base_plan_node']);
            $allEvents[] = $eventData;
        }

        if (empty($allEvents)) {
            $this->repository->delistEventsNotIn([]);

            return 0;
        }

        $activePlanIds = collect($allEvents)->pluck('plan_id')->unique()->toArray();

        // Process the events in chunks
        foreach (array_chunk($allEvents, self::BATCH_SIZE) as $chunk) {
            $this->repository->upsertBatch($chunk);
        }

        // Mark events that are no longer in the feed as 'delisted'
        $this->repository->delistEventsNotIn(array_unique($activePlanIds));

        Log::info(count($allEvents).' unique events processed successfully.');

        return count($allEvents);
    }

    public function syncEvents(): int
    {
        if ($this->isCircuitBreakerOpen()) {
            Log::warning('Circuit breaker is open. Skipping synchronization.');

            return 0;
        }

        Log::info('Starting event synchronization.');
        $tempFilePath = tempnam(sys_get_temp_dir(), 'events_xml');

        try {
            $response = $this->apiClient->fetchEventsTo($tempFilePath);

            if (! $response->successful()) {
                Log::error('Failed to fetch events from provider.', ['status' => $response->status()]);
                $response->throw(); // Re-throw to make the job fail and retry
            }

            $this->resetCircuitBreakerFailures();

            return $this->processAndPersistEvents($tempFilePath);

        } catch (Exception $e) {
            Log::error('An exception occurred during event synchronization.', ['error' => $e->getMessage()]);
            throw $e; // Re-throw for the job to handle retries
        } finally {
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    protected function isCircuitBreakerOpen(): bool
    {
        return Cache::get(self::CIRCUIT_BREAKER_KEY, 0) > time();
    }

    protected function resetCircuitBreakerFailures(): void
    {
        Cache::forget(self::CIRCUIT_BREAKER_KEY.'_failures');
    }

    public function openCircuitBreaker(): void
    {
        Cache::put(self::CIRCUIT_BREAKER_KEY, now()->addMinutes(15)->getTimestamp());
        Log::critical('Circuit breaker has been opened for 15 minutes.');
    }
}
