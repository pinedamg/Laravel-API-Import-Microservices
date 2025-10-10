<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Support\Facades\Log;

class EventRepository
{
    /**
     * Upserts a batch of events into the database.
     */
    public function upsertBatch(array $eventsBatch): void
    {
        if (empty($eventsBatch)) {
            return;
        }

        Event::upsert($eventsBatch, ['base_plan_id', 'plan_id'], ['title', 'starts_at', 'ends_at', 'min_price', 'max_price', 'status', 'updated_at']);
        Log::info(count($eventsBatch).' events synchronized in a batch.');
    }

    /**
     * Marks events as 'delisted' if they are not in the provided list of active plan IDs.
     *
     * @return int The number of events delisted.
     */
    public function delistEventsNotIn(array $activePlanIds): int
    {
        $delistedCount = Event::where('status', 'active')->whereNotIn('plan_id', $activePlanIds)->update(['status' => 'delisted']);

        if ($delistedCount > 0) {
            Log::info($delistedCount.' events marked as delisted.');
        }

        return $delistedCount;
    }
}
