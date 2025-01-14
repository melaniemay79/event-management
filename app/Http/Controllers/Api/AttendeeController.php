<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendeeResource;
use App\Http\Traits\CanLoadRelationships;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class AttendeeController extends Controller implements HasMiddleware
{
    use CanLoadRelationships;

    private array $relations = ['user'];

    public static function middleware(): array
    {
        return [
            new Middleware(middleware: 'auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index(Event $event): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', $event);

        $attendees = $this->loadRelationships($event->attendees()->latest());

        return AttendeeResource::collection($attendees->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Event $event): AttendeeResource
    {
        Gate::authorize('create', $event);

        $attendee = $this->loadRelationships($event->attendees()->create([
            ...$request->validate([
                'user_id' => 'required|exists:users,id',
            ]),
        ]));

        return new AttendeeResource($this->loadRelationships($attendee));
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event, Attendee $attendee): AttendeeResource
    {
        Gate::authorize('view', $attendee);

        return new AttendeeResource($this->loadRelationships($attendee));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event, Attendee $attendee): JsonResponse
    {
        Gate::authorize('delete', $attendee);

        $attendee->delete();

        return response()->json(status: 204);
    }
}
