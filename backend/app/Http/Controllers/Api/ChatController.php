<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\ChatPreviewResource;
use App\Http\Resources\MessageResource;
use App\Models\Chat;
use App\Models\Message;
use App\Services\FriendshipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(
        private readonly FriendshipService $friendshipService,
        private readonly \App\Services\BlockService $blockService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $chats = Chat::query()
            ->where('status', '!=', Chat::STATUS_DECLINED)
            ->whereHas('participants', fn ($q) => $q
                ->where('users.id', $user->id)
                ->whereNull('chat_participants.hidden_at'))
            ->with(['participants', 'messages' => fn ($q) => $q->latest('sent_at')->limit(1)])
            // Deterministische Basis-Reihenfolge (sonst liefert MySQL ohne ORDER BY
            // keine stabile Zeilenfolge, was bei Ties in sortByDesc dazu führte, dass
            // die Liste bei jedem Poll leicht anders sortiert war -> sichtbares "Flackern").
            ->orderByDesc('id')
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

        abort_if(
            $this->blockService->isBlockedEitherWay($user->id, $partnerId),
            403,
            'Mit dieser Person kannst du nicht chatten.',
        );

        abort_unless(
            $this->friendshipService->areFriends($user->id, $partnerId),
            403,
            'Ihr müsst befreundet sein, um einen Chat zu starten.',
        );

        $existing = $this->findChatBetween($user->id, $partnerId);

        if ($existing) {
            if ($existing->status === Chat::STATUS_DECLINED) {
                $existing->delete();
            } else {
                $this->unhideForUser($existing, $user->id);

                return response()->json([
                    'chat' => (new ChatPreviewResource($existing->load(['participants', 'messages']), $user))->resolve(),
                ]);
            }
        }

        $chat = DB::transaction(function () use ($user, $partnerId, $request) {
            $chat = Chat::create([
                'status' => Chat::STATUS_PENDING,
                'requested_by' => $user->id,
            ]);
            $chat->participants()->attach([$user->id, $partnerId]);

            if ($request->filled('message')) {
                $chat->messages()->create([
                    'sender_id' => $user->id,
                    'text' => $request->string('message')->trim()->value(),
                    'sent_at' => now(),
                ]);
            }

            return $chat->load(['participants', 'messages']);
        });

        return response()->json([
            'chat' => (new ChatPreviewResource($chat, $user))->resolve(),
        ], 201);
    }

    public function accept(Request $request, Chat $chat): JsonResponse
    {
        $this->authorizeChat($request, $chat);
        $user = $request->user();

        abort_unless($chat->status === Chat::STATUS_PENDING, 422, 'Diese Anfrage ist nicht mehr offen.');
        abort_if($chat->requested_by === $user->id, 403, 'Du kannst deine eigene Anfrage nicht annehmen.');

        $chat->status = Chat::STATUS_ACCEPTED;
        $chat->save();
        $chat->load(['participants', 'messages']);

        return response()->json([
            'chat' => (new ChatPreviewResource($chat, $user))->resolve(),
        ]);
    }

    public function decline(Request $request, Chat $chat): JsonResponse
    {
        $this->authorizeChat($request, $chat);
        $user = $request->user();

        abort_unless($chat->status === Chat::STATUS_PENDING, 422, 'Diese Anfrage ist nicht mehr offen.');
        abort_if($chat->requested_by === $user->id, 403, 'Du kannst deine eigene Anfrage nicht ablehnen.');

        $chat->status = Chat::STATUS_DECLINED;
        $chat->save();

        return response()->json(['message' => 'Chat-Anfrage abgelehnt.']);
    }

    public function destroy(Request $request, Chat $chat): JsonResponse
    {
        $this->authorizeChat($request, $chat);

        $now = now();
        $chat->participants()->updateExistingPivot($request->user()->id, [
            'hidden_at' => $now,
            'cleared_at' => $now,
        ]);

        return response()->json(['message' => 'Chat für dich gelöscht.']);
    }

    public function messages(Request $request, Chat $chat): AnonymousResourceCollection
    {
        $this->authorizeChat($request, $chat);

        $user = $request->user();
        $chat->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $messages = $chat->messages()
            ->orderBy('sent_at')
            ->when(
                $this->clearedAtFor($chat, $user->id),
                fn ($q, $clearedAt) => $q->where('sent_at', '>', $clearedAt),
            )
            ->get();

        return MessageResource::collection($messages);
    }

    public function sendMessage(StoreMessageRequest $request, Chat $chat): MessageResource
    {
        $this->authorizeChat($request, $chat);

        $user = $request->user();

        $partnerId = $chat->participants()
            ->where('users.id', '!=', $user->id)
            ->value('users.id');

        abort_unless(
            $partnerId && $this->friendshipService->areFriends($user->id, (int) $partnerId),
            403,
            'Ihr seid nicht (mehr) befreundet. Schicke zuerst eine Freundschaftsanfrage.',
        );

        $this->assertCanSendMessage($chat, $user->id);

        if ($chat->status === Chat::STATUS_PENDING && $chat->requested_by !== $user->id) {
            $chat->status = Chat::STATUS_ACCEPTED;
            $chat->save();
        }

        $attachmentUrl = null;
        $attachmentType = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments/'.$chat->id, 'public');
            $attachmentUrl = '/storage/'.$path;
            $attachmentType = str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'pdf';
            $attachmentName = $file->getClientOriginalName();
        }

        $text = trim((string) $request->input('text', ''));

        $message = $chat->messages()->create([
            'sender_id' => $user->id,
            'text' => $text !== '' ? $text : null,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'attachment_name' => $attachmentName,
            'sent_at' => now(),
        ]);

        if ($partnerId) {
            $this->unhideForUser($chat, (int) $partnerId);
        }

        $chat->touch();

        return new MessageResource($message);
    }

    public function editMessage(Request $request, Chat $chat, Message $message): MessageResource
    {
        $this->authorizeChat($request, $chat);
        $user = $request->user();

        abort_unless($message->chat_id === $chat->id, 404);
        abort_unless($message->sender_id === $user->id, 403, 'Du kannst nur eigene Nachrichten bearbeiten.');
        abort_if($message->deleted_at !== null, 422, 'Diese Nachricht wurde bereits gelöscht.');

        $data = $request->validate(['text' => ['required', 'string', 'max:2000']]);

        $message->text = trim($data['text']);
        $message->edited_at = now();
        $message->save();

        return new MessageResource($message);
    }

    public function deleteMessage(Request $request, Chat $chat, Message $message): JsonResponse
    {
        $this->authorizeChat($request, $chat);
        $user = $request->user();

        abort_unless($message->chat_id === $chat->id, 404);
        abort_unless($message->sender_id === $user->id, 403, 'Du kannst nur eigene Nachrichten löschen.');

        $message->deleted_at = now();
        $message->save();

        return response()->json(['message' => 'Nachricht gelöscht.']);
    }

    public function sync(Request $request, Chat $chat): JsonResponse
    {
        $this->authorizeChat($request, $chat);

        $user = $request->user();
        $chat->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $query = $chat->messages()->orderBy('sent_at');

        if ($clearedAt = $this->clearedAtFor($chat, $user->id)) {
            $query->where('sent_at', '>', $clearedAt);
        }

        $after = $request->integer('after');
        $query->where(fn ($q) => $q
            ->where('id', '>', $after)
            ->orWhere('updated_at', '>=', now()->subSeconds(30)));

        $messages = $query->get();

        $partnerId = $chat->participants()
            ->where('users.id', '!=', $user->id)
            ->value('users.id');

        $partnerTyping = $partnerId
            && $chat->status === Chat::STATUS_ACCEPTED
            && Cache::has($this->typingCacheKey($chat->id, (int) $partnerId));

        $isFriend = $partnerId && $this->friendshipService->areFriends($user->id, (int) $partnerId);

        return response()->json([
            'messages' => MessageResource::collection($messages)->resolve(),
            'partner_typing' => $partnerTyping,
            'status' => $chat->status,
            'requested_by' => $chat->requested_by,
            'is_incoming_request' => $chat->status === Chat::STATUS_PENDING && $chat->requested_by !== $user->id,
            'is_outgoing_request' => $chat->status === Chat::STATUS_PENDING && $chat->requested_by === $user->id,
            'is_friend' => (bool) $isFriend,
        ]);
    }

    public function typing(Request $request, Chat $chat): JsonResponse
    {
        $this->authorizeChat($request, $chat);

        abort_unless($chat->status === Chat::STATUS_ACCEPTED, 422);

        Cache::put(
            $this->typingCacheKey($chat->id, $request->user()->id),
            true,
            now()->addSeconds(4),
        );

        return response()->json(['ok' => true]);
    }

    private function findChatBetween(int $userId, int $partnerId): ?Chat
    {
        return Chat::query()
            ->whereHas('participants', fn ($q) => $q->where('users.id', $userId))
            ->whereHas('participants', fn ($q) => $q->where('users.id', $partnerId))
            ->with(['participants', 'messages'])
            ->first();
    }

    private function assertCanSendMessage(Chat $chat, int $userId): void
    {
        if ($chat->status === Chat::STATUS_DECLINED) {
            abort(403, 'Diese Chat-Anfrage wurde abgelehnt.');
        }

        if ($chat->status === Chat::STATUS_ACCEPTED) {
            return;
        }

        if ($chat->requested_by === $userId) {
            $hasSent = $chat->messages()->where('sender_id', $userId)->exists();
            abort_if($hasSent, 403, 'Warte auf Annahme deiner Chat-Anfrage.');
        }
    }

    private function unhideForUser(Chat $chat, int $userId): void
    {
        $chat->participants()->updateExistingPivot($userId, ['hidden_at' => null]);
    }

    private function clearedAtFor(Chat $chat, int $userId): ?string
    {
        return $chat->participants()
            ->where('users.id', $userId)
            ->value('chat_participants.cleared_at');
    }

    private function typingCacheKey(int $chatId, int $userId): string
    {
        return "chat:{$chatId}:typing:{$userId}";
    }

    private function authorizeChat(Request $request, Chat $chat): void
    {
        $isParticipant = $chat->participants()->where('users.id', $request->user()->id)->exists();

        abort_unless($isParticipant, 403, 'Kein Zugriff auf diesen Chat.');
    }
}
