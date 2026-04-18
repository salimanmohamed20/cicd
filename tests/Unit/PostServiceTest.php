<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostService;
    }

    // ─── Create Tests ────────────────────────────────

    public function test_create_sets_published_at_when_published(): void
    {
        $user = User::factory()->create();

        $post = $this->service->create([
            'title' => 'Test Post Title',
            'body' => str_repeat('body ', 20),
            'published' => true,
        ], $user);

        $this->assertTrue($post->published);
        $this->assertNotNull($post->published_at);
    }

    public function test_create_draft_has_no_published_at(): void
    {
        $user = User::factory()->create();

        $post = $this->service->create([
            'title' => 'Draft Post Title',
            'body' => str_repeat('body ', 20),
            'published' => false,
        ], $user);

        $this->assertFalse($post->published);
        $this->assertNull($post->published_at);
    }

    public function test_create_assigns_correct_user(): void
    {
        $user = User::factory()->create();

        $post = $this->service->create([
            'title' => 'User Post Title',
            'body' => str_repeat('body ', 20),
            'published' => false,
        ], $user);

        $this->assertEquals($user->id, $post->user_id);
    }

    // ─── Publish Tests ───────────────────────────────

    public function test_publish_sets_published_true_and_sets_date(): void
    {
        $post = Post::factory()->draft()->create();

        $this->assertFalse($post->published);
        $this->assertNull($post->published_at);

        $published = $this->service->publish($post);

        $this->assertTrue($published->published);
        $this->assertNotNull($published->published_at);
    }

    // ─── Word Count Tests ─────────────────────────────

    public function test_get_word_count_returns_correct_count(): void
    {
        $post = Post::factory()->make([
            'body' => 'This is a test post with exactly ten words here',
        ]);

        $count = $this->service->getWordCount($post);

        $this->assertEquals(10, $count);
    }

    public function test_get_word_count_ignores_html_tags(): void
    {
        $post = Post::factory()->make([
            'body' => '<p>Hello <strong>World</strong></p>',
        ]);

        $count = $this->service->getWordCount($post);

        $this->assertEquals(2, $count); // "Hello" و "World" بس
    }

    // ─── Reading Time Tests ───────────────────────────

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $post = Post::factory()->make(['body' => 'Short text']);

        $time = $this->service->getReadingTime($post);

        $this->assertGreaterThanOrEqual(1, $time);
    }

    public function test_reading_time_calculates_based_on_200_wpm(): void
    {
        // 400 كلمة ÷ 200 كلمة/دقيقة = 2 دقيقة
        $post = Post::factory()->make([
            'body' => implode(' ', array_fill(0, 400, 'word')),
        ]);

        $time = $this->service->getReadingTime($post);

        $this->assertEquals(2, $time);
    }
}
