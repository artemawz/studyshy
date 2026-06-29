<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Services\InterestService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(
        private readonly InterestService $interestService,
    ) {}

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        if ($request->has('pub_name')) {
            $user->pub_name = trim($request->string('pub_name')->value());
        }

        if ($request->has('avatar_url')) {
            $user->avatar_url = $request->input('avatar_url');
        }

        if ($request->has('bio')) {
            $user->bio = $request->input('bio');
        }

        if ($request->has('uni')) {
            $user->uni = $request->string('uni')->value();
        }

        $user->save();

        if ($request->has('course') || $request->has('semester')) {
            $course = $user->courses()->firstOrNew([]);
            if ($request->has('course')) {
                $course->name = $request->string('course')->value();
            }
            if ($request->has('semester')) {
                $course->semester = $request->integer('semester');
            }
            $course->user_id = $user->id;
            $course->save();
        }

        if ($request->has('interests')) {
            $this->interestService->syncUserInterests($user, $request->input('interests', []));
        }

        $user->load(['courses', 'interests']);

        return new UserResource($user);
    }

    public function uploadAvatar(UploadAvatarRequest $request): UserResource
    {
        $user = $request->user();

        $this->deleteStoredAvatar($user->avatar_url);

        $path = $request->file('avatar')->store('avatars/'.$user->id, 'public');
        $user->avatar_url = Storage::disk('public')->url($path);
        $user->save();
        $user->load(['courses', 'interests']);

        return new UserResource($user);
    }

    private function deleteStoredAvatar(?string $url): void
    {
        if ($url === null || ! str_contains($url, '/storage/avatars/')) {
            return;
        }

        $path = Str::after($url, '/storage/');

        if ($path !== $url && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
