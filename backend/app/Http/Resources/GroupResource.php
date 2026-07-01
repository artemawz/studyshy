<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Group */
class GroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;

        $myPivot = $this->relationLoaded('members')
            ? $this->members->firstWhere('id', $viewerId)?->pivot
            : null;
        $lastMessage = $this->relationLoaded('messages') ? $this->messages->first() : null;

        // Nur für Mitglieder relevant – sonst hätte jede Gruppe mit Nachrichten
        // fälschlich "unread", solange man ihr nicht beigetreten ist.
        $unread = $myPivot !== null
            && $lastMessage
            && $lastMessage->sender_id !== $viewerId
            && (! $myPivot->last_read_at || $lastMessage->sent_at->gt($myPivot->last_read_at));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'uni' => $this->uni,
            'course' => $this->course,
            'createdBy' => $this->created_by,
            'isOwner' => $this->created_by === $viewerId,
            'memberCount' => $this->whenLoaded('members', fn () => $this->members->count()),
            'isMember' => $this->whenLoaded(
                'members',
                fn () => $this->members->contains('id', $viewerId),
            ),
            'members' => $this->whenLoaded(
                'members',
                fn () => $this->members->map(fn ($m) => [
                    'id' => $m->id,
                    'pub_name' => $m->pub_name,
                    'avatarUrl' => $m->avatar_url,
                    'role' => $m->pivot->role,
                ])->values(),
            ),
            'unread' => (bool) $unread,
            'lastMessageAt' => $lastMessage?->sent_at?->toIso8601String(),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
