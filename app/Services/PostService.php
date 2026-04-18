<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;

class PostService
{
    public function create(array $data, User $user): Post
    {
        return $user->posts()->create([
            'title' => $data['title'],
            'body' => $data['body'],
            'published' => $data['published'] ?? false,
            'published_at' => ($data['published'] ?? false) ? now() : null,
        ]);
    }

    public function publish(Post $post): Post
    {
        $post->update([
            'published' => true,
            'published_at' => now(),
        ]);

        return $post;
    }

    public function getWordCount(Post $post): int
    {
        return str_word_count(strip_tags($post->body));
    }

    public function getReadingTime(Post $post): int
    {
        $wordsPerMinute = 200;
        $words = $this->getWordCount($post);

        return (int) ceil($words / $wordsPerMinute);
    }
}
