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
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
