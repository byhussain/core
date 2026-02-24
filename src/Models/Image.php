<?php

namespace SmartTill\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    /** @use HasFactory<\Database\Factories\ImageFactory> */
    use HasFactory;

    protected $fillable = [
        'path',
        'sort_order',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function syncFor(Model $imageable, array $paths): void
    {
        $normalizedPaths = collect($paths)
            ->map(fn ($path) => trim((string) $path))
            ->filter()
            ->unique()
            ->values();

        $imageable->images()->delete();

        foreach ($normalizedPaths as $index => $path) {
            $imageable->images()->create([
                'path' => $path,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
