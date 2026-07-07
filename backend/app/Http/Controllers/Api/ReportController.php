<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'reported_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'context' => ['nullable', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:100'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        Report::create([
            'reporter_id' => $user->id,
            'reported_user_id' => $data['reported_user_id'] ?? null,
            'context' => $data['context'] ?? null,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Danke für deine Meldung. Wir schauen uns das an.',
        ], 201);
    }
}
