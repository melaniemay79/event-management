<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Traits\CanLoadRelationships;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller implements HasMiddleware
{
    use CanLoadRelationships;

    private array $relations = ['user', 'attendees', 'attendees.user'];

    public static function middleware(): array
    {
        return [
            new Middleware(middleware: 'auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Event::class);
        
        $query = $this->loadRelationships(Event::query());

        return EventResource::collection($query->latest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): EventResource
    {
        Gate::authorize('create', Event::class);

        $event = Event::create([
            ...$request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'required|date',
                'end_time' => 'required|date|after:start_time',
            ]),
            'user_id' => $request->user()->id,
        ]);

        return new EventResource($this->loadRelationships($event));
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): EventResource
    {
        Gate::authorize('view', $event);

        return new EventResource($this->loadRelationships($event));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Event $event): EventResource
    {
        Gate::authorize('update', $event);

        $event->update([
            ...$request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after:start_time',
            ]),
        ]);

        return new EventResource($event);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): JsonResponse
    {
        Gate::authorize('delete', $event);

        $event->attendees()->delete();
        $event->delete();

        return response()->json(status: 204);
    }
}
