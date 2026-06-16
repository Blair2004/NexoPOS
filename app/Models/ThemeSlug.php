<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSlug extends Model
{
    use HasFactory;

    protected $table = 'nexopos_themes_slugs';

    protected $fillable = [
        'feature',
        'slug',
        'theme_namespace',
    ];

    /**
     * Get the slug for a specific feature and theme.
     *
     * @param string $feature
     * @param string|null $themeNamespace
     * @return string
     */
    public static function getSlugForFeature(string $feature, ?string $themeNamespace = null): string
    {
        $slug = self::where('feature', $feature)
            ->where('theme_namespace', $themeNamespace)
            ->first();

        return $slug ? $slug->slug : $feature;
    }
}
