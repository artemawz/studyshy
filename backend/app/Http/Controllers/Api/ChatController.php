<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\ChatPreviewResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = Chat::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with(['participants', 'messages' => fn ($q) => $q->latest('sent_at')->limit(1)])
            ->get()
            ->sortByDesc(fn (Chat $chat) => $chat->messages->first()?->sent_at ?? $chat->updated_at)
            ->values();

        return response()->json([
            'data' => $chats->map(
                fn (Chat $chat) => (new ChatPreviewResource($chat, $user))->resolve()
            )->values(),
        ]);
    }

    public function store(StoreChatRequest $request): JsonResponse
    {
        $user = $request->user();
        $partnerId = $request->integer('partner_id');

        $existing = Chat::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $partnerId))
            ->with('participants')
            ->first();

        if ($existing) {
            return response()->json([
                'chat' => (new ChatPreviewResource($existing, $user))->resolve(),
            ]);
        }

        $chat = DB::transaction(function () use ($user, $partnerId) {
            $chat = Chat::create();
            $chat->participants()->attach([$user->id, $partnerId]);
            $chat->load('participants');

            return $chat;
        });

        return response()->json([
            'chat' => (new ChatPreviewResource($chat, $user))->resolve(),
        ], 201);
    }

    public function messages(Request $request, Chat $chat): AnonymousResourceCollection
    {
        $this->authorizeChat($request, $chat);

        $user = $request->user();
        $chat->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $messages = $chat->messages()->orderBy('sent_at')->get();

        return MessageResource::collection($messages);
    }

    public function sendMessage(StoreMessageRequest $request, Chat $chat): MessageResource
    {
        $this->authorizeChat($request, $chat);

        $message = $chat->messages()->create([
            'sender_id' => $request->user()->id,
            'text' => $request->string('text')->trim()->value(),
            'sent_at' => now(),
        ]);

        $chat->touch();

        return new MessageResource($message);
    }

    private function authorizeChat(Request $request, Chat $chat): void
    {
        $isParticipant = $chat->participants()->where('users.id', $request->user()->id)->exists();

        abort_unless($isParticipant, 403, 'Kein Zugriff auf diesen Chat.');
    }
}
