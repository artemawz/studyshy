<?php

namespace App\Http\Resources;

use App\Models\Chat;
use App\Models\Friendship;
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

        $myPivot = $this->resource->participants
            ->firstWhere('id', $this->currentUser->id)
            ?->pivot;

        $clearedAt = $myPivot?->cleared_at;
        $lastReadAt = $myPivot?->last_read_at;

        $lastMessage = $this->resource->messages()
            ->when($clearedAt, fn ($q) => $q->where('sent_at', '>', $clearedAt))
            ->latest('sent_at')
            ->first();

        $unread = false;
        if ($lastMessage && $lastMessage->sender_id !== $this->currentUser->id) {
            $unread = ! $lastReadAt || $lastMessage->sent_at->gt($lastReadAt);
        }

        $isIncomingRequest = $this->status === Chat::STATUS_PENDING
            && $this->requested_by !== $this->currentUser->id;
        $isOutgoingRequest = $this->status === Chat::STATUS_PENDING
            && $this->requested_by === $this->currentUser->id;

        $preview = $lastMessage?->text ?? '';
        if ($preview === '' && $lastMessage?->attachment_url) {
            $preview = $lastMessage->attachment_type === 'image'
                ? '📷 Bild'
                : '📎 '.($lastMessage->attachment_name ?? 'Datei');
        }
        if ($preview === '' && $isIncomingRequest) {
            $preview = 'Möchte mit dir chatten';
        } elseif ($preview === '' && $isOutgoingRequest) {
            $preview = 'Anfrage ausstehend';
        }

        $isFriend = $partner !== null && $this->isFriendWith($partner->id);

        return [
            'id' => $this->id,
            'partnerId' => $partner?->id,
            'partnerName' => $partner?->pub_name ?? 'Unbekannt',
            'partnerAvatarUrl' => $partner?->avatar_url,
            'lastMessage' => $preview,
            'updatedAt' => ($lastMessage?->sent_at ?? $this->updated_at)?->toIso8601String(),
            'unread' => $unread || $isIncomingRequest,
            'status' => $this->status,
            'requestedBy' => $this->requested_by,
            'isIncomingRequest' => $isIncomingRequest,
            'isOutgoingRequest' => $isOutgoingRequest,
            'isFriend' => $isFriend,
        ];
    }

    private function isFriendWith(int $partnerId): bool
    {
        return Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($q) use ($partnerId) {
                $q->where(['requester_id' => $this->currentUser->id, 'addressee_id' => $partnerId])
                    ->orWhere(['requester_id' => $partnerId, 'addressee_id' => $this->currentUser->id]);
            })
            ->exists();
    }
}
