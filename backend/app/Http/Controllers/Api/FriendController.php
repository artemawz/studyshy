<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function __construct(
        private readonly FriendshipService $friendshipService,
        private readonly \App\Services\BlockService $blockService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $friends = Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(fn ($q) => $q->where('requester_id', $user->id)->orWhere('addressee_id', $user->id))
            ->with(['requester', 'addressee'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Friendship $f) => $this->serializeFriendship($f, $user));

        $incoming = Friendship::query()
            ->where('status', Friendship::STATUS_PENDING)
            ->where('addressee_id', $user->id)
            ->with(['requester'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Friendship $f) => $this->serializeRequest($f, $user));

        $outgoing = Friendship::query()
            ->where('status', Friendship::STATUS_PENDING)
            ->where('requester_id', $user->id)
            ->with(['addressee'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Friendship $f) => $this->serializeRequest($f, $user));

        return response()->json([
            'friends' => $friends,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ]);
    }

    public function status(Request $request, int $userId): JsonResponse
    {
        abort_if($userId === $request->user()->id, 422, 'Ungültiger Nutzer.');

        $status = $this->friendshipService->statusFor($request->user(), $userId);
        $friendship = $this->friendshipService->findBetween($request->user()->id, $userId);

        return response()->json([
            'status' => $status,
            'friendship_id' => $friendship?->id,
        ]);
    }

    public function request(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'not_in:'.$request->user()->id],
        ]);

        abort_if(
            $this->blockService->isBlockedEitherWay($request->user()->id, (int) $data['user_id']),
            403,
            'Mit dieser Person ist keine Interaktion möglich.',
        );

        $friendship = $this->friendshipService->sendRequest(
            $request->user(),
            $data['user_id'],
        );

        return response()->json([
            'friendship' => $this->serializeRequest($friendship, $request->user()),
            'status' => 'pending_outgoing',
        ], 201);
    }

    public function accept(Request $request, Friendship $friendship): JsonResponse
    {
        $this->authorizeIncoming($request, $friendship);

        $friendship->status = Friendship::STATUS_ACCEPTED;
        $friendship->save();
        $friendship->load(['requester', 'addressee']);

        return response()->json([
            'friendship' => $this->serializeFriendship($friendship, $request->user()),
            'status' => 'accepted',
        ]);
    }

    public function decline(Request $request, Friendship $friendship): JsonResponse
    {
        $this->authorizeIncoming($request, $friendship);

        $friendship->status = Friendship::STATUS_DECLINED;
        $friendship->save();

        return response()->json(['message' => 'Freundschaftsanfrage abgelehnt.']);
    }

    public function destroy(Request $request, Friendship $friendship): JsonResponse
    {
        $user = $request->user();
        $isParticipant = $friendship->requester_id === $user->id
            || $friendship->addressee_id === $user->id;

        abort_unless($isParticipant, 403);

        if ($friendship->status === Friendship::STATUS_PENDING && $friendship->requester_id === $user->id) {
            $friendship->delete();

            return response()->json(['message' => 'Freundschaftsanfrage zurückgezogen.']);
        }

        $friendship->delete();

        return response()->json(['message' => 'Freundschaft beendet.']);
    }

    private function authorizeIncoming(Request $request, Friendship $friendship): void
    {
        abort_unless($friendship->addressee_id === $request->user()->id, 403);
        abort_unless($friendship->status === Friendship::STATUS_PENDING, 422);
    }

    /** @return array<string, mixed> */
    private function serializeRequest(Friendship $friendship, User $viewer): array
    {
        $partner = $friendship->requester_id === $viewer->id
            ? $friendship->addressee
            : $friendship->requester;

        return [
            'id' => $friendship->id,
            'status' => $friendship->status,
            'partner' => (new UserResource($partner))->resolve(),
            'created_at' => $friendship->created_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function serializeFriendship(Friendship $friendship, User $viewer): array
    {
        $partner = $friendship->requester_id === $viewer->id
            ? $friendship->addressee
            : $friendship->requester;

        return [
            'id' => $friendship->id,
            'partner' => (new UserResource($partner))->resolve(),
            'since' => $friendship->updated_at?->toIso8601String(),
        ];
    }
}
