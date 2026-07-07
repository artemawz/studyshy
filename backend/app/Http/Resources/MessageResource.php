<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deleted = $this->deleted_at !== null;

        return [
            'id' => $this->id,
            'chatId' => $this->chat_id,
            'senderId' => $this->sender_id,
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
