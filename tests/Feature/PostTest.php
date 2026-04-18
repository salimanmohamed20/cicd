<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    // ─── Index Tests ─────────────────────────────────

    public function test_anyone_can_see_published_posts(): void
    {
        // 3 منشورة + 2 مش منشورة
        Post::factory()->count(3)->published()->create();
        Post::factory()->count(2)->draft()->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // بس الـ published
    }

    public function test_posts_are_paginated(): void
    {
        Post::factory()->count(20)->published()->create();

        $response = $this->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ])
            ->assertJsonCount(15, 'data'); // 15 per page
    }

    // ─── Show Tests ──────────────────────────────────

    public function test_anyone_can_view_a_published_post(): void
    {
        $post = Post::factory()->published()->create();

        $this->getJson("/api/posts/{$post->id}")
            ->assertStatus(200)
            ->assertJson(['id' => $post->id]);
    }

    public function test_draft_post_returns_404(): void
    {
        $post = Post::factory()->draft()->create();

        $this->getJson("/api/posts/{$post->id}")
            ->assertStatus(404);
    }

    // ─── Store Tests ─────────────────────────────────

    public function test_authenticated_user_can_create_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/posts', [
                'title'     => 'My First Post Title',
                'body'      => 'This is the body content of my post, it needs to be at least 20 characters.',
                'published' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'post' => ['id', 'title', 'body', 'user'],
            ]);

        $this->assertDatabaseHas('posts', [
            'title'   => 'My First Post Title',
            'user_id' => $user->id,
        ]);
    }

    public function test_post_creation_fails_with_short_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/posts', [
                'title' => 'Hi',  // أقصر من 5 حروف
                'body'  => str_repeat('content ', 10),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $this->postJson('/api/posts', [
            'title' => 'Test Post',
            'body'  => 'Content here...',
        ])->assertStatus(401);
    }

    // ─── Update Tests ────────────────────────────────

    public function test_user_can_update_their_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Title Here',
                'body'  => str_repeat('updated body content ', 5),
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('posts', [
            'id'    => $post->id,
            'title' => 'Updated Title Here',
        ]);
    }

    public function test_user_cannot_update_another_users_post(): void
    {
        $owner  = User::factory()->create();
        $hacker = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($hacker)
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Hacked!',
                'body'  => str_repeat('hacked content ', 5),
            ])
            ->assertStatus(403);
    }

    // ─── Delete Tests ────────────────────────────────

    public function test_user_can_delete_their_own_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertStatus(200);

        // SoftDelete - موجود في الـ DB بس مع deleted_at
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_user_cannot_delete_another_users_post(): void
    {
        $owner  = User::factory()->create();
        $hacker = User::factory()->create();
        $post   = Post::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($hacker)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
