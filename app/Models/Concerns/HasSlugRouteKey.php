<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasSlugRouteKey
{
    protected static function bootHasSlugRouteKey(): void
    {
        static::created(function (Model $model): void {
            if (blank($model->getAttribute('slug'))) {
                $model->forceFill([
                    'slug' => Str::limit(Str::slug($model->slugSource()), 165, '').'-'.$model->getKey(),
                ])->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $record = $this->newQuery()->where($field, $value)->first();

        if ($record !== null || $field !== 'slug' || ! ctype_digit((string) $value)) {
            return $record;
        }

        // Keep existing bookmarks and old numeric links working.
        return $this->newQuery()->where($this->getKeyName(), $value)->first();
    }

    abstract protected function slugSource(): string;
}
