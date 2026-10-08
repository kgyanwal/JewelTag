<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Per-store feature switches, stored in site_settings (each tenant has its own DB).
 *   store_credit_enabled  - default ON  (existing stores already use it)
 *   loyalty_enabled       - default OFF (new feature, opt-in per store)
 */
class StoreFeatures
{
    private static ?array $cache = null;

    private static function load(): array
    {
        if (self::$cache === null) {
            try {
                self::$cache = DB::table('site_settings')
                    ->whereIn('key', ['store_credit_enabled', 'loyalty_enabled'])
                    ->pluck('value', 'key')->toArray();
            } catch (\Throwable $e) {
                self::$cache = [];
            }
        }
        return self::$cache;
    }

    private static function flag(string $key, bool $default): bool
    {
        $v = self::load()[$key] ?? null;
        if ($v === null || $v === '') {
            return $default;
        }
        return in_array(strtolower((string) $v), ['1', 'true', 'on', 'yes'], true);
    }

    public static function storeCredit(): bool { return self::flag('store_credit_enabled', true); }

    public static function loyalty(): bool { return self::flag('loyalty_enabled', false); }

    /** Call after saving settings so the same request sees fresh values. */
    public static function flush(): void { self::$cache = null; }
}