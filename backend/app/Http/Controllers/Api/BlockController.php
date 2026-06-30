<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Block;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $blocked = Block::query()
            ->where('blocker_id', $user->id)
            ->with('blocked')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Block $block) => [
                'id' => $block->id,
                'user' => (new UserResource($block->blocked))->resolve(),
            ]);

        return response()->json(['data' => $blocked]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'not_in:'.$user->id],
        ]);

        Block::firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_id' => $data['user_id'],
        ]);

        // Bestehende Freundschaft/Anfragen zwischen den beiden auflösen
        Friendship::query()
            ->where(fn ($q) => $q->where('requester_id', $user->id)->where('addressee_id', $data['user_id']))
            ->orWhere(fn ($q) => $q->where('requester_id', $data['user_id'])->where('addressee_id', $user->id))
            ->delete();

        return response()->json(['message' => 'Nutzer blockiert.'], 201);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        Block::query()
            ->where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();

        return response()->json(['message' => 'Blockierung aufgehoben.']);
    }
}
