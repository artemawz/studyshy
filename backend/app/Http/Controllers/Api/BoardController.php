<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BoardCommentResource;
use App\Http\Resources\BoardPostResource;
use App\Models\BoardPost;
use App\Services\BlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BoardController extends Controller
{
    public const CATEGORIES = ['Lernpartner', 'Material', 'Wohnen', 'Mitfahrt', 'Sonstiges'];

    public function __construct(
        private readonly BlockService $blockService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $hidden = $request->user()
            ? $this->blockService->hiddenUserIds($request->user()->id)
            : [];

        $posts = BoardPost::query()
            ->with('author')
            ->withCount('comments')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->query('category')))
            ->whereNotIn('user_id', $hidden)
            ->orderByDesc('created_at')
            ->get();

        return BoardPostResource::collection($posts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'body' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $post = BoardPost::create([
            'user_id' => $request->user()->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'body' => $data['body'],
        ]);

        $post->load('author')->loadCount('comments');

        return response()->json([
            'data' => (new BoardPostResource($post))->resolve($request),
        ], 201);
    }

    public function destroy(Request $request, BoardPost $post): JsonResponse
    {
        abort_unless($post->user_id === $request->user()->id, 403, 'Du kannst nur eigene Beiträge löschen.');

        $post->delete();

        return response()->json(['message' => 'Beitrag gelöscht.']);
    }

    public function comments(Request $request, BoardPost $post): AnonymousResourceCollection
    {
        $hidden = $request->user()
            ? $this->blockService->hiddenUserIds($request->user()->id)
            : [];

        $comments = $post->comments()
            ->with('author')
            ->whereNotIn('user_id', $hidden)
            ->orderBy('created_at')
            ->get();

        return BoardCommentResource::collection($comments);
    }

    public function storeComment(Request $request, BoardPost $post): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $comment->load('author');

        return response()->json([
            'data' => (new BoardCommentResource($comment))->resolve($request),
        ], 201);
    }

    public function destroyComment(Request $request, \App\Models\BoardComment $comment): JsonResponse
    {
        $isOwner = $comment->user_id === $request->user()->id;
        $isPostOwner = $comment->post && $comment->post->user_id === $request->user()->id;
        abort_unless($isOwner || $isPostOwner, 403, 'Keine Berechtigung.');

        $comment->delete();

        return response()->json(['message' => 'Kommentar gelöscht.']);
    }
}
