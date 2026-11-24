<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function valueFor(string $key, ?string $default = null): ?string
    {
        return static::query()->whereKey($key)->value('value') ?? $default;
    }

    public static function branding(): array
    {
        return [
            'name' => static::valueFor('system_name', config('app.name', 'Gestión Docente')),
            'short_name' => static::valueFor('system_short_name', 'GD'),
            'subtitle' => static::valueFor('system_subtitle', 'Organización académica'),
            'logo_path' => static::valueFor('system_logo'),
            'primary_color' => static::valueFor('primary_color', '#176B87'),
            'primary_hover' => static::valueFor('primary_hover', '#10546B'),
        ];
    }
}
