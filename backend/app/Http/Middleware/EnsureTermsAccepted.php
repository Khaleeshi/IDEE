<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un membre accepte les conditions d'utilisation et la charte communautaire avant
 * d'utiliser IDEE, puis à chaque nouvelle version des textes (sections 8 et 9).
 */
class EnsureTermsAccepted
{
    /**
     * Pages ouvertes avant l'acceptation : les textes eux-mêmes, la déconnexion et les droits sur ses données.
     */
    private const WEB_ROUTES = ['terms.*', 'legal.*', 'logout', 'account.*', 'email.verify'];

    private const API_PATHS = ['api/me', 'api/me/terms', 'api/me/export', 'api/auth/*'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? $request->user('sanctum');

        if ($user === null || $user->hasAcceptedTerms()) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            if ($request->is(...self::API_PATHS)) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Acceptez les conditions d\'utilisation et la charte communautaire pour continuer.',
                'terms_required' => true,
            ], Response::HTTP_FORBIDDEN);
        }

        if ($request->routeIs(...self::WEB_ROUTES)) {
            return $next($request);
        }

        // Le message d'accueil de la connexion s'affiche sur la page d'acceptation.
        $request->session()->reflash();

        return redirect()->guest(route('terms.show'));
    }
}
