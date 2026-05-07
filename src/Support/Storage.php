<?php

namespace WallaceMartinss\FilamentSecurity\Support;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Centralized resolvers for the DB connection and cache store used by the
 * package, honoring `filament-security.connection` and
 * `filament-security.cache_store` config keys.
 */
class Storage
{
    public static function db(): ConnectionInterface
    {
        return DB::connection(config('filament-security.connection'));
    }

    public static function cache(): CacheRepository
    {
        $store = config('filament-security.cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }

    /**
     * RateLimiter bound to the configured cache store, so throttle counters
     * don't get partitioned/duplicated by tenant-aware cache prefixes.
     */
    public static function rateLimiter(): RateLimiter
    {
        return new RateLimiter(static::cache());
    }
}
