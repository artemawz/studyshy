<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\BlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function __construct(
        private readonly BlockService $blockService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $hidden = $request->user()
            ? $this->blockService->hiddenUserIds($request->user()->id)
            : [];

        $events = Event::query()
            ->with(['creator', 'participants'])
            ->whereNotIn('created_by', $hidden)
            ->where('starts_at', '>=', now()->subHours(3))
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return EventResource::collection($events);
    }

    public function show(Request $request, Event $event): EventResource
    {
        $event->load(['creator', 'participants']);

        return new EventResource($event);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'location' => ['nullable', 'string', 'max:160'],
            'uni' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date', 'after:now'],
        ]);

        $event = Event::create([
            'created_by' => $user->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'] ?? null,
            'uni' => $data['uni'] ?? null,
            'starts_at' => $data['starts_at'],
        ]);

        $event->participants()->attach($user->id, ['joined_at' => now()]);
        $event->load(['creator', 'participants']);

        return response()->json([
            'data' => (new EventResource($event))->resolve($request),
        ], 201);
    }

    public function join(Request $request, Event $event): JsonResponse
    {
        $user = $request->user();

        if (! $event->participants()->where('users.id', $user->id)->exists()) {
            $event->participants()->attach($user->id, ['joined_at' => now()]);
        }

        $event->load(['creator', 'participants']);

        return response()->json([
            'data' => (new EventResource($event))->resolve($request),
        ]);
    }

    public function leave(Request $request, Event $event): JsonResponse
    {
        $event->participants()->detach($request->user()->id);
        $event->load(['creator', 'participants']);

        return response()->json([
            'data' => (new EventResource($event))->resolve($request),
        ]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        abort_unless($event->created_by === $request->user()->id, 403, 'Nur Ersteller:innen können das Event löschen.');

        $event->delete();

        return response()->json(['message' => 'Event gelöscht.']);
    }
}
