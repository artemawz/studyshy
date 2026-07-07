<?php

namespace App\Services;

use App\Models\Interest;
use App\Models\User;

class InterestService
{
    /** @param list<string> $interestNames */
    public function syncUserInterests(User $user, array $interestNames): void
    {
        $names = collect($interestNames)
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '' && mb_strtolower($name) !== 'andere')
            ->unique()
            ->values();

        $ids = $names->map(fn (string $name) => Interest::firstOrCreate(['name' => $name])->id);

        $user->interests()->sync($ids);
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public function allForFilters()
    {
        return collect(config('studyshy.interests'))
            ->merge(Interest::query()->orderBy('name')->pluck('name'))
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '' && mb_strtolower($name) !== 'andere')
            ->unique()
            ->sort()
            ->values();
    }
}
