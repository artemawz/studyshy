<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\StudentFilterService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentFilterService $filterService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = Auth::guard('sanctum')->user();

        $query = User::query()
            ->with(['courses', 'interests'])
            ->when($user, fn ($q) => $q->where('id', '!=', $user->id));

        $this->filterService->apply($query, $request);

        return UserResource::collection($query->orderBy('pub_name')->get());
    }

    public function show(int $id): UserResource
    {
        $user = User::with(['courses', 'interests'])->findOrFail($id);

        return new UserResource($user);
    }
}
