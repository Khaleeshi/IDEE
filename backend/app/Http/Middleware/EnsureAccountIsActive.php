<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Déconnecte un compte suspendu ou exclu par la modération (section 8).
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? $request->user('sanctum');

        if ($user?->isRestricted()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                abort(Response::HTTP_FORBIDDEN, $user->restrictionMessage());
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['phone' => $user->restrictionMessage()]);
        }

        return $next($request);
    }
}
