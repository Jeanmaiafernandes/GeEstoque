<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Acesso simples às configurações (chave-valor) da tabela settings.
 */
class Settings
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return Setting::where('key', $key)->value('value') ?? $default;
    }

    /** Valor inteiro de uma configuração, com fallback. */
    public static function int(string $key, int $default): int
    {
        $value = self::get($key);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    public static function companyLogoPath(): ?string
    {
        return self::get('company_logo_path');
    }

    /** URL pública da logo (requer `php artisan storage:link`). */
    public static function companyLogoUrl(): ?string
    {
        $path = self::companyLogoPath();

        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** Logo embutida como data URI base64 — para uso no PDF (dompdf). */
    public static function companyLogoBase64(): ?string
    {
        $path = self::companyLogoPath();
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }

        $mime = $disk->mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
    }
}
