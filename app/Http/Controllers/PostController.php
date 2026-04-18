<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private PostService $postService) {}

    public function index(): JsonResponse
    {
        $posts = Post::published()
            ->with('user:id,name')
            ->latest('published_at')
            ->paginate(15);

        return response()->json($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postService->create(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('user'),
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        abort_if(! $post->published, 404, 'Post not found');

        return response()->json($post->load('user'));
    }

    public function update(StorePostRequest $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403, 'Forbidden');

        $post->update($request->validated());

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->fresh('user'),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id, 403, 'Forbidden');

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }
}
