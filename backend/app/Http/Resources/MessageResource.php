<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chatId' => $this->chat_id,
            'senderId' => $this->sender_id,
            'text' => $this->text,
            'sentAt' => $this->sent_at?->toIso8601String(),
        ];
    }
}
