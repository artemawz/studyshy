<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pub_name' => $this->pub_name,
            'uni' => $this->uni,
            'bio' => $this->bio,
            'avatarUrl' => $this->avatar_url,
            'courses' => $this->whenLoaded('courses', fn () => $this->courses->map(fn ($course) => [
                'name' => $course->name,
                'degree' => $course->degree,
                'semester' => $course->semester,
            ])),
            'interests' => $this->whenLoaded('interests', fn () => $this->interests->pluck('name')),
        ];
    }
}
