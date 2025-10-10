<?php

namespace App\Jobs;

use App\Services\ProviderSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SyncProviderEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The URL of the provider API.
     */
    public string $providerUrl;

    /**
     * Create a new job instance.
     */
    public function __construct(string $providerUrl)
    {
        $this->providerUrl = $providerUrl;
    }

    /**
     * Execute the job.
     */
    public function handle(ProviderSyncService $syncService): int
    {
        return $syncService->syncEvents();
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Event synchronization failed: '.$exception->getMessage(), [
            'job_id' => $this->job->getJobId(),
            'provider_url' => $this->providerUrl,
            'exception' => $exception->getTraceAsString(),
        ]);

        // Resolve the service from the container and open the circuit breaker
        $syncService = app(ProviderSyncService::class);
        $syncService->openCircuitBreaker();
    }
}
