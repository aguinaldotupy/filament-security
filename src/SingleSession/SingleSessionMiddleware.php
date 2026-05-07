<?php

namespace WallaceMartinss\FilamentSecurity\SingleSession;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use WallaceMartinss\FilamentSecurity\EventLog\Enums\SecurityEventType;
use WallaceMartinss\FilamentSecurity\EventLog\Models\SecurityEvent;
use WallaceMartinss\FilamentSecurity\Support\Storage;

class SingleSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('filament-security.single_session.enabled', false)) {
            return $next($request);
        }

        if (! $request->hasSession() || ! $request->user()) {
            return $next($request);
        }

        $currentSessionId = session()->getId();
        $userId = $request->user()->getAuthIdentifier();
        $cacheKey = SingleSessionService::cacheKey($userId);
        $lifetime = config('session.lifetime', 120);

        // After login + session regeneration: activate this session
        if (session()->pull('filament-security:activate-session')) {
            Storage::cache()->put($cacheKey, $currentSessionId, now()->addMinutes($lifetime));

            return $next($request);
        }

        $activeSessionId = Storage::cache()->get($cacheKey);

        // No tracking yet — register this session
        if ($activeSessionId === null) {
            Storage::cache()->put($cacheKey, $currentSessionId, now()->addMinutes($lifetime));

            return $next($request);
        }

        // This is the active session — refresh TTL and continue
        if ($activeSessionId === $currentSessionId) {
            Storage::cache()->put($cacheKey, $currentSessionId, now()->addMinutes($lifetime));

            return $next($request);
        }

        // Session mismatch — this session was superseded by a newer login
        SecurityEvent::record(SecurityEventType::SessionTerminated->value, [
            'ip_address' => $request->ip(),
            'metadata' => [
                'user_id' => $userId,
                'superseded_by' => $activeSessionId,
            ],
        ]);

        SingleSessionService::$isForcedLogout = true;

        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        SingleSessionService::$isForcedLogout = false;

        return redirect()->to($this->getLoginUrl());
    }

    protected function getLoginUrl(): string
    {
        try {
            return filament()->getLoginUrl();
        } catch (\Throwable) {
            return '/login';
        }
    }
}
