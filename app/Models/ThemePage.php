<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemePage extends Model
{
    use HasFactory;

    protected $table = 'nexopos_themes_pages';

    protected $fillable = [
        'title',
        'slug',
        'parent_id',
        'content',
        'status',
        'theme_namespace',
        'author_id',
        'published_at',
    ];

    protected $casts = [
        'content' => 'array',
        'published_at' => 'datetime',
    ];

    /**
     * Get the author of the page.
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the parent page.
     */
    public function parent()
    {
        return $this->belongsTo(ThemePage::class, 'parent_id');
    }

    /**
     * Get child pages.
     */
    public function children()
    {
        return $this->hasMany(ThemePage::class, 'parent_id');
    }

    /**
     * Scope a query to only include published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Get the full path of the page (including parent slugs).
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->slug];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->slug);
            $parent = $parent->parent;
        }

        return implode('/', $path);
    }
}
