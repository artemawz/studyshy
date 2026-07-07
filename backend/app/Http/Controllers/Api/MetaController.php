<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
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
        $universities = collect(config('studyshy.universities'));
        $configCourses = collect(config('studyshy.courses', []));

        // Real in der DB vorkommende Studiengänge je Hochschule
        $dbByUni = UserCourse::query()
            ->join('users', 'users.id', '=', 'user_courses.user_id')
            ->select('users.uni as uni', 'user_courses.name as name')
            ->distinct()
            ->get()
            ->groupBy('uni')
            ->map(fn ($rows) => $rows->pluck('name'));

        $sortFn = fn ($a, $b) => strcoll($a, $b);

        $coursesByUni = $universities->mapWithKeys(function (string $uni) use ($configCourses, $dbByUni, $sortFn) {
            $list = collect($configCourses->get($uni, []))
                ->merge($dbByUni->get($uni, collect()))
                ->map(fn ($name) => trim($name))
                ->filter()
                ->unique()
                ->sort($sortFn)
                ->values();

            return [$uni => $list];
        });

        $courses = $coursesByUni
            ->flatten()
            ->unique()
            ->sort($sortFn)
            ->values();

        return response()->json([
            'unis' => $universities,
            'courses' => $courses,
            'coursesByUni' => $coursesByUni,
            'degrees' => collect(config('studyshy.degrees')),
            'interests' => $this->interestService->allForFilters(),
            'semesters' => collect(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10+']),
        ]);
    }

    public function stats(): JsonResponse
    {
        $students = User::count();
        $universities = count(config('studyshy.universities'));

        // "Studenten verbunden" = bestätigte Freundschaften zwischen registrierten Studierenden
        $connections = Friendship::where('status', Friendship::STATUS_ACCEPTED)->count();

        return response()->json([
            'students' => number_format($students, 0, ',', '.'),
            'universities' => (string) $universities,
            'connections' => number_format($connections, 0, ',', '.'),
            'anonymous' => '100%',
        ]);
    }
}
