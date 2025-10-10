<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="EventResource",
 *     title="Event Resource",
 *     description="Event resource object",
 *
 *     @OA\Property(property="id", type="integer", format="int64", description="ID of the event"),
 *     @OA\Property(property="title", type="string", description="Title of the event"),
 *     @OA\Property(property="start_date", type="string", format="date", description="Start date of the event"),
 *     @OA\Property(property="start_time", type="string", format="time", description="Start time of the event"),
 *     @OA\Property(property="end_date", type="string", format="date", description="End date of the event"),
 *     @OA\Property(property="end_time", type="string", format="time", description="End time of the event"),
 *     @OA\Property(property="min_price", type="number", format="float", description="Minimum price of the event"),
 *     @OA\Property(property="max_price", type="number", format="float", description="Maximum price of the event")
 * )
 */
class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'start_date' => $this->starts_at->format('Y-m-d'),
            'start_time' => $this->starts_at->format('H:i:s'),
            'end_date' => $this->ends_at->format('Y-m-d'),
            'end_time' => $this->ends_at->format('H:i:s'),
            'min_price' => (float) $this->min_price,
            'max_price' => (float) $this->max_price,
        ];
    }
}
