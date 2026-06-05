<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $sessionKey = 'user_last_seen_synced_at_'.$user->getKey();
            $lastSyncedAt = $request->session()->get($sessionKey);

            if (!$lastSyncedAt || now()->diffInMinutes($lastSyncedAt) >= 5) {
                $user->forceFill([
                    'last_seen_at' => now(),
                ])->saveQuietly();

                $request->session()->put($sessionKey, now()->toDateTimeString());
            }
        } else {
            $request->session()->forget('user_last_seen_synced_at');
        }

        return $next($request);
    }
}
