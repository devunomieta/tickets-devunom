<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBranding extends Model
{
    protected $table = 'platform_brandings';

    protected $fillable = [
        'app_name',
        'primary_color',
        'theme_mode',
        'accent_color',
        'logo_url',
        'dark_logo_url',
        'favicon_url',
    ];

    public static function current(): self
    {
        return self::query()->first() ?? new self([
            'app_name' => config('app.name', 'CortDevs Tickets'),
            'primary_color' => '#8b5cf6',
            'theme_mode' => 'dark',
            'accent_color' => '#10b981',
            'logo_url' => null,
            'dark_logo_url' => null,
            'favicon_url' => null,
        ]);
    }

    public function toPublicArray(): array
    {
        return [
            'app_name' => $this->app_name ?: config('app.name', 'CortDevs Tickets'),
            'primary_color' => $this->primary_color ?: '#8b5cf6',
            'theme_mode' => $this->theme_mode ?: 'dark',
            'accent_color' => $this->accent_color ?: '#10b981',
            'logo_url' => $this->logo_url,
            'dark_logo_url' => $this->dark_logo_url,
            'favicon_url' => $this->favicon_url,
        ];
    }
}
