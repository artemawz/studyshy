<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'uni' => $this->uni,
            'startsAt' => $this->starts_at?->toIso8601String(),
            'createdBy' => $this->created_by,
            'isOwner' => $this->created_by === $viewerId,
            'creatorName' => $this->whenLoaded('creator', fn () => $this->creator->pub_name),
            'participantCount' => $this->whenLoaded('participants', fn () => $this->participants->count()),
            'isAttending' => $this->whenLoaded(
                'participants',
                fn () => $this->participants->contains('id', $viewerId),
            ),
            'participants' => $this->whenLoaded(
                'participants',
                fn () => $this->participants->map(fn ($p) => [
                    'id' => $p->id,
                    'pub_name' => $p->pub_name,
                    'avatarUrl' => $p->avatar_url,
                ])->values(),
            ),
        ];
    }
}
