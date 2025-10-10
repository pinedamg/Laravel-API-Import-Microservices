<?php

namespace App\Services\ProviderSync;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ProviderApiClient
{
    protected string $providerUrl;

    public function __construct()
    {
        $this->providerUrl = config('services.provider.url');
    }

    /**
     * Fetches the events XML from the provider and sinks it into the given file path.
     *
     * @param  string  $sinkPath  The path to save the downloaded file.
     */
    public function fetchEventsTo(string $sinkPath): Response
    {
        return Http::timeout(60) // Increased timeout for potentially large downloads
            ->sink($sinkPath)
            ->get($this->providerUrl);
    }
}
