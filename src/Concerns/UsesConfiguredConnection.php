<?php

namespace WallaceMartinss\FilamentSecurity\Concerns;

/**
 * Pins the model to the connection configured in `filament-security.connection`.
 *
 * Falls back to the model's parent behavior (default app connection) when the
 * config value is null, preserving backwards compatibility.
 */
trait UsesConfiguredConnection
{
    public function getConnectionName(): ?string
    {
        return config('filament-security.connection') ?? parent::getConnectionName();
    }
}
