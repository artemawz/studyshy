<?php

namespace App\Services;

use App\Models\Friendship;
use App\Models\User;

class FriendshipService
{
    public function findBetween(int $userA, int $userB): ?Friendship
    {
        return Friendship::query()
            ->where(fn ($q) => $q->where('requester_id', $userA)->where('addressee_id', $userB))
            ->orWhere(fn ($q) => $q->where('requester_id', $userB)->where('addressee_id', $userA))
            ->first();
    }

    public function areFriends(int $userA, int $userB): bool
    {
        $friendship = $this->findBetween($userA, $userB);

        return $friendship?->status === Friendship::STATUS_ACCEPTED;
    }

    /** @return 'none'|'pending_outgoing'|'pending_incoming'|'accepted'|'declined' */
    public function statusFor(User $viewer, int $otherUserId): string
    {
        if ($viewer->id === $otherUserId) {
            return 'none';
        }

        $friendship = $this->findBetween($viewer->id, $otherUserId);

        if (! $friendship) {
            return 'none';
        }

        if ($friendship->status === Friendship::STATUS_ACCEPTED) {
            return 'accepted';
        }

        if ($friendship->status === Friendship::STATUS_DECLINED) {
            return 'declined';
        }

        return $friendship->requester_id === $viewer->id
            ? 'pending_outgoing'
            : 'pending_incoming';
    }

    public function sendRequest(User $requester, int $addresseeId): Friendship
    {
        $existing = $this->findBetween($requester->id, $addresseeId);

        if ($existing) {
            if ($existing->status === Friendship::STATUS_ACCEPTED) {
                abort(422, 'Ihr seid bereits befreundet.');
            }

            if ($existing->status === Friendship::STATUS_PENDING) {
                if ($existing->requester_id === $requester->id) {
                    return $existing;
                }

                abort(422, 'Diese Person hat dir bereits eine Freundschaftsanfrage gesendet.');
            }

            $existing->update([
                'requester_id' => $requester->id,
                'addressee_id' => $addresseeId,
                'status' => Friendship::STATUS_PENDING,
            ]);

            return $existing->fresh(['requester', 'addressee']);
        }

        return Friendship::create([
            'requester_id' => $requester->id,
            'addressee_id' => $addresseeId,
            'status' => Friendship::STATUS_PENDING,
        ])->load(['requester', 'addressee']);
    }
}
