<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 店の設定 (printer_ip など)。値は JSON で保存し、型を保って返す。
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row === null ? $default : $row->value;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
