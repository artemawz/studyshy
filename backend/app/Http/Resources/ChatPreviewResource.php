<?php

namespace App\Http\Resources;

use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Chat */
class ChatPreviewResource extends JsonResource
{
    public function __construct(
        Chat $resource,
        private readonly User $currentUser,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $partner = $this->resource->partnerFor($this->currentUser);
        $lastMessage = $this->resource->messages()->latest('sent_at')->first();
        $lastReadAt = $this->resource->participants
            ->firstWhere('id', $this->currentUser->id)
            ?->pivot
            ?->last_read_at;

        $unread = false;
        if ($lastMessage && $lastMessage->sender_id !== $this->currentUser->id) {
            $unread = ! $lastReadAt || $lastMessage->sent_at->gt($lastReadAt);
        }

        return [
            'id' => $this->id,
            'partnerId' => $partner?->id,
            'partnerName' => $partner?->pub_name ?? 'Unbekannt',
            'lastMessage' => $lastMessage?->text ?? '',
            'updatedAt' => ($lastMessage?->sent_at ?? $this->updated_at)?->toIso8601String(),
            'unread' => $unread,
        ];
    }
}
