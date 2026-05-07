<?php

namespace WallaceMartinss\FilamentSecurity\Concerns;

/**
 * For migrations: routes the migration to the connection configured in
 * `filament-security.connection` (returns null when unset, which means
 * "use the default app connection" — backwards compatible).
 */
trait MigratesOnConfiguredConnection
{
    public function getConnection(): ?string
    {
        return config('filament-security.connection');
    }
}
