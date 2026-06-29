<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Models\UserCourse;
use App\Services\InterestService;
use Illuminate\Http\JsonResponse;

class MetaController extends Controller
{
    public function __construct(
        private readonly InterestService $interestService,
    ) {}

    public function filters(): JsonResponse
    {
        return response()->json([
            'unis' => collect(config('studyshy.universities')),
            'courses' => UserCourse::query()->distinct()->orderBy('name')->pluck('name')->values(),
            'interests' => $this->interestService->allForFilters(),
            'semesters' => collect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10+']),
        ]);
    }

    public function stats(): JsonResponse
    {
        $students = User::count();
        $universities = count(config('studyshy.universities'));
        $connections = Message::count();

        return response()->json([
            'students' => number_format($students, 0, ',', '.'),
            'universities' => (string) $universities,
            'connections' => $connections >= 1000 ? number_format($connections, 0, ',', '.').'+' : (string) $connections,
            'anonymous' => '100%',
        ]);
    }
}
