<?php
/**
 * ------------------------------------------------------------------
 *  Papastro Framework
 *  Hybrid PHP + Astro-style Modular Monolith Framework
 *
 *  @author     kangpcode (Dhafa Nazula Permadi)
 *  @copyright  Copyright (c) kangpcode
 *  @license    Free to use & modify, watermark must remain in source
 * ------------------------------------------------------------------
 */

declare(strict_types=1);

namespace App\Modules\Blog\Models;

use Papastro\ORM\Model;

class Post extends Model
{
    protected string $table    = 'posts';
    protected array  $fillable = ['title', 'slug', 'excerpt', 'body', 'user_id', 'published', 'published_at'];
    protected bool   $timestamps = true;

    /**
     * Get only published posts.
     */
    public static function published(): \Papastro\ORM\ModelQueryBuilder
    {
        return static::where('published', 1);
    }

    /**
     * Find a post by its slug.
     */
    public static function findBySlug(string $slug): ?static
    {
        $row = static::query()->where('slug', $slug)->first();
        return $row ? static::newFromBuilder($row) : null;
    }

    /**
     * Auto-generate slug from title if not set.
     */
    public function setTitleAttribute(string $value): void
    {
        $this->attributes['title'] = $value;

        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = slug($value);
        }
    }

    /**
     * Get the estimated reading time in minutes.
     */
    public function getReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->body ?? ''));
        return max(1, (int) round($wordCount / 200));
    }
}
