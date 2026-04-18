<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'user_id',
        'published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope: جيب المنشورات المنشورة بس
    public function scopePublished($query)
    {
        return $query->where('published', true)
            ->whereNotNull('published_at');
    }

    // Accessor: اختصر الـ body لو طويل
    public function getExcerptAttribute(): string
    {
        return \Str::limit($this->body, 150);
    }
}
