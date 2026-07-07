<?php

namespace App\Services;

use App\Models\Block;

class BlockService
{
    /**
     * IDs of users that should be hidden from the given user:
     * everyone they blocked and everyone who blocked them.
     *
     * @return list<int>
     */
    public function hiddenUserIds(int $userId): array
    {
        $blockedByMe = Block::query()
            ->where('blocker_id', $userId)
            ->pluck('blocked_id');

        $blockedMe = Block::query()
            ->where('blocked_id', $userId)
            ->pluck('blocker_id');

        return $blockedByMe->merge($blockedMe)->unique()->values()->all();
    }

    public function isBlockedEitherWay(int $userA, int $userB): bool
    {
        return Block::query()
            ->where(fn ($q) => $q->where('blocker_id', $userA)->where('blocked_id', $userB))
            ->orWhere(fn ($q) => $q->where('blocker_id', $userB)->where('blocked_id', $userA))
            ->exists();
    }
}
