<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryLibraryEntry extends Model
{
    protected $table = 'category_library';

    protected $fillable = [
        'title',
        'default_weight',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'default_weight' => 'integer',
            'usage_count' => 'integer',
        ];
    }

    /**
     * Alimente la bibliothèque au fil des audits : les intitulés les plus
     * employés remontent en tête de l'autocomplétion.
     */
    public static function remember(string $title, int $weight = 1): void
    {
        $title = trim($title);

        if ($title === '') {
            return;
        }

        $entry = static::whereRaw('LOWER(title) = ?', [mb_strtolower($title)])->first();

        if ($entry) {
            $entry->increment('usage_count');

            return;
        }

        static::create([
            'title' => $title,
            'default_weight' => $weight,
            'usage_count' => 1,
        ]);
    }

    public static function suggestions(int $limit = 50): array
    {
        return static::query()
            ->orderByDesc('usage_count')
            ->orderBy('title')
            ->limit($limit)
            ->pluck('title')
            ->all();
    }
}
