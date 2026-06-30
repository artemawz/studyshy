<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupMessageResource;
use App\Models\Group;
use App\Models\GroupMessage;
use App\Services\BlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupMessageController extends Controller
{
    public function __construct(
        private readonly BlockService $blockService,
    ) {}

    public function index(Request $request, Group $group): AnonymousResourceCollection
    {
        $this->authorizeMember($request, $group);
        $this->markRead($request, $group);

        $messages = $group->messages()
            ->with('sender')
            ->whereNotIn('sender_id', $this->blockService->hiddenUserIds($request->user()->id))
            ->orderBy('sent_at')
            ->get();

        return GroupMessageResource::collection($messages);
    }

    public function sync(Request $request, Group $group): JsonResponse
    {
        $this->authorizeMember($request, $group);
        $this->markRead($request, $group);

        $after = $request->integer('after');

        $messages = $group->messages()
            ->with('sender')
            ->whereNotIn('sender_id', $this->blockService->hiddenUserIds($request->user()->id))
            ->where(fn ($q) => $q
                ->where('id', '>', $after)
                ->orWhere('updated_at', '>=', now()->subSeconds(30)))
            ->orderBy('sent_at')
            ->get();

        return response()->json([
            'messages' => GroupMessageResource::collection($messages)->resolve($request),
        ]);
    }

    public function store(Request $request, Group $group): GroupMessageResource
    {
        $this->authorizeMember($request, $group);

        $request->validate([
            'text' => ['nullable', 'string', 'max:2000', 'required_without:attachment'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,pdf', 'max:5120'],
        ]);

        $attachmentUrl = null;
        $attachmentType = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('group-attachments/'.$group->id, 'public');
            $attachmentUrl = '/storage/'.$path;
            $attachmentType = str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'pdf';
            $attachmentName = $file->getClientOriginalName();
        }

        $text = trim((string) $request->input('text', ''));

        $message = $group->messages()->create([
            'sender_id' => $request->user()->id,
            'text' => $text !== '' ? $text : null,
            'attachment_url' => $attachmentUrl,
            'attachment_type' => $attachmentType,
            'attachment_name' => $attachmentName,
            'sent_at' => now(),
        ]);

        $group->touch();
        $message->load('sender');

        return new GroupMessageResource($message);
    }

    public function update(Request $request, Group $group, GroupMessage $message): GroupMessageResource
    {
        $this->authorizeMember($request, $group);
        abort_unless($message->group_id === $group->id, 404);
        abort_unless($message->sender_id === $request->user()->id, 403, 'Du kannst nur eigene Nachrichten bearbeiten.');
        abort_if($message->deleted_at !== null, 422, 'Diese Nachricht wurde bereits gelöscht.');

        $data = $request->validate(['text' => ['required', 'string', 'max:2000']]);

        $message->text = trim($data['text']);
        $message->edited_at = now();
        $message->save();
        $message->load('sender');

        return new GroupMessageResource($message);
    }

    public function destroy(Request $request, Group $group, GroupMessage $message): JsonResponse
    {
        $this->authorizeMember($request, $group);
        abort_unless($message->group_id === $group->id, 404);

        $isOwnMessage = $message->sender_id === $request->user()->id;
        $isGroupOwner = $group->created_by === $request->user()->id;
        abort_unless($isOwnMessage || $isGroupOwner, 403, 'Keine Berechtigung.');

        $message->deleted_at = now();
        $message->save();

        return response()->json(['message' => 'Nachricht gelöscht.']);
    }

    private function authorizeMember(Request $request, Group $group): void
    {
        $isMember = $group->members()->where('users.id', $request->user()->id)->exists();
        abort_unless($isMember, 403, 'Du bist kein Mitglied dieser Gruppe.');
    }

    private function markRead(Request $request, Group $group): void
    {
        $group->members()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);
    }
}
