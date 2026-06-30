<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\InterestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly InterestService $interestService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'email' => $request->string('email')->lower()->value(),
            'password' => Hash::make($request->string('password')->value()),
            'pub_name' => 'Student #'.Str::lower(Str::random(3)),
            'uni' => $request->string('uni')->value(),
            'bio' => '',
            'avatar_url' => 'https://i.pravatar.cc/150?img='.random_int(1, 64),
        ]);

        $user->courses()->create([
            'name' => $request->string('course')->value(),
            'degree' => $request->input('degree'),
            'semester' => $request->integer('semester'),
        ]);

        $this->interestService->syncUserInterests($user, $request->input('interests', []));

        $token = $user->createToken('studyshy')->plainTextToken;
        $user->load(['courses', 'interests']);

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->lower()->value())->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Die Anmeldedaten sind ungültig.'],
            ]);
        }

        $token = $user->createToken('studyshy')->plainTextToken;
        $user->load(['courses', 'interests']);

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Abgemeldet.']);
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();
        $user->load(['courses', 'interests']);

        return new UserResource($user);
    }
}
