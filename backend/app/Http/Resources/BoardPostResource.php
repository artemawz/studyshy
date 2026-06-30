<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BoardPost */
class BoardPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;

        return [
            'id' => $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'body' => $this->body,
            'authorId' => $this->user_id,
            'isOwner' => $this->user_id === $viewerId,
            'commentCount' => $this->comments_count ?? $this->whenLoaded('comments', fn () => $this->comments->count()),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author->id,
                'pub_name' => $this->author->pub_name,
                'avatarUrl' => $this->author->avatar_url,
                'uni' => $this->author->uni,
            ]),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
