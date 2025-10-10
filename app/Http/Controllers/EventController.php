<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;
use OpenApi\Annotations as OA;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $events = Event::all();
        return view('events.index', compact('events'));
    }

    /**
     * @OA\Get(
     *     path="/api/search",
     *     summary="Search for events within a date range",
     *     tags={"Events"},
     *
     *     @OA\Parameter(
     *         name="starts_at",
     *         in="query",
     *         required=true,
     *         description="Start date and time (ISO 8601 format, e.g., 2025-01-01T00:00:00)",
     *
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *
     *     @OA\Parameter(
     *         name="ends_at",
     *         in="query",
     *         required=true,
     *         description="End date and time (ISO 8601 format, e.g., 2025-12-31T23:59:59)",
     *
     *         @OA\Schema(type="string", format="date-time")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *
     *         @OA\JsonContent(
     *             type="array",
     *
     *             @OA\Items(ref="#/components/schemas/EventResource")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object", example={"starts_at": {"The starts at field is required."}}))
     *     )
     *
     * )
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
        ]);

        $events = Event::where('status', 'active')
            ->where(function ($query) use ($request) {
                $query->where('starts_at', '<=', $request->input('ends_at'))
                    ->where('ends_at', '>=', $request->input('starts_at'));
            })
            ->get();

        return EventResource::collection($events);
    }
}
