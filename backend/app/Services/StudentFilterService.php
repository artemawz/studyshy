<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StudentFilterService
{
    public function apply(Builder $query, Request $request): Builder
    {
        $unis = $this->arrayParam($request, 'uni');
        $courses = $this->arrayParam($request, 'course');
        $interests = $this->arrayParam($request, 'interest');
        $semesters = $this->arrayParam($request, 'semester');

        if ($unis !== []) {
            $query->whereIn('uni', $unis);
        }

        if ($courses !== []) {
            $query->whereHas('courses', fn (Builder $q) => $q->whereIn('name', $courses));
        }

        if ($interests !== []) {
            $query->whereHas('interests', fn (Builder $q) => $q->whereIn('name', $interests));
        }

        if ($semesters !== []) {
            $query->whereHas('courses', function (Builder $q) use ($semesters) {
                $q->where(function (Builder $inner) use ($semesters) {
                    foreach ($semesters as $semester) {
                        if ($semester === '10+') {
                            $inner->orWhere('semester', '>=', 10);
                        } else {
                            $inner->orWhere('semester', (int) $semester);
                        }
                    }
                });
            });
        }

        return $query;
    }

    /** @return list<string> */
    private function arrayParam(Request $request, string $key): array
    {
        $value = $request->query($key, []);

        if (is_string($value)) {
            return $value === '' ? [] : [$value];
        }

        return array_values(array_filter((array) $value, fn ($item) => $item !== null && $item !== ''));
    }
}
