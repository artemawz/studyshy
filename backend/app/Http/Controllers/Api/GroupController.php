<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $groups = Group::query()
            ->withCount('members')
            ->with('members')
            ->orderByDesc('created_at')
            ->get();

        return GroupResource::collection($groups);
    }

    public function show(Request $request, Group $group): GroupResource
    {
        $group->load('members');

        return new GroupResource($group);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'uni' => ['nullable', 'string', 'max:120'],
            'course' => ['nullable', 'string', 'max:120'],
        ]);

        $group = Group::create([
            'created_by' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'uni' => $data['uni'] ?? null,
            'course' => $data['course'] ?? null,
        ]);

        $group->members()->attach($user->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $group->load('members');

        return response()->json([
            'data' => (new GroupResource($group))->resolve($request),
        ], 201);
    }

    public function join(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        if (! $group->members()->where('users.id', $user->id)->exists()) {
            $group->members()->attach($user->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }

        $group->load('members');

        return response()->json([
            'data' => (new GroupResource($group))->resolve($request),
        ]);
    }

    public function leave(Request $request, Group $group): JsonResponse
    {
        $user = $request->user();

        abort_if($group->created_by === $user->id, 422, 'Als Ersteller:in kannst du die Gruppe nur löschen, nicht verlassen.');

        $group->members()->detach($user->id);

        return response()->json(['message' => 'Gruppe verlassen.']);
    }

    public function destroy(Request $request, Group $group): JsonResponse
    {
        abort_unless($group->created_by === $request->user()->id, 403, 'Nur Ersteller:innen können die Gruppe löschen.');

        $group->delete();

        return response()->json(['message' => 'Gruppe gelöscht.']);
    }
}
