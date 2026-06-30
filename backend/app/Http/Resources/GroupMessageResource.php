<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GroupMessage */
class GroupMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deleted = $this->deleted_at !== null;

        return [
            'id' => $this->id,
            'groupId' => $this->group_id,
            'senderId' => $this->sender_id,
            'senderName' => $this->whenLoaded('sender', fn () => $this->sender->pub_name),
            'senderAvatarUrl' => $this->whenLoaded('sender', fn () => $this->sender->avatar_url),
            'text' => $deleted ? null : $this->text,
            'attachmentUrl' => $deleted ? null : $this->attachment_url,
            'attachmentType' => $deleted ? null : $this->attachment_type,
            'attachmentName' => $deleted ? null : $this->attachment_name,
            'edited' => $this->edited_at !== null,
            'deleted' => $deleted,
            'sentAt' => $this->sent_at?->toIso8601String(),
        ];
    }
}
