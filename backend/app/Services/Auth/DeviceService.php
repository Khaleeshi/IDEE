<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Appareils connectés à un compte (CP-06) : l'application (jetons) et les
 * navigateurs (sessions du site). Le membre voit où il est connecté et coupe
 * un appareil perdu ou volé, sans changer de numéro.
 *
 * L'identifiant d'une session du site n'est jamais montré : on expose son empreinte.
 */
class DeviceService
{
    /**
     * @return Collection<int, array{id: string, kind: string, name: string, last_active_at: ?Carbon, signed_in_at: ?Carbon, current: bool}>
     */
    public function list(User $user, ?int $currentTokenId = null, ?string $currentSessionId = null): Collection
    {
        $tokens = $user->tokens()->get()->map(fn (PersonalAccessToken $token) => [
            'id' => 'app-'.$token->id,
            'kind' => 'application',
            'name' => $token->name ?: 'Application IDEE',
            'last_active_at' => $token->last_used_at ?? $token->created_at,
            'signed_in_at' => $token->created_at,
            'current' => $token->id === $currentTokenId,
        ]);

        $sessions = $this->sessionsTable()
            ? DB::table('sessions')->where('user_id', $user->id)->get()->map(fn (object $session) => [
                'id' => 'web-'.hash('sha256', $session->id),
                'kind' => 'navigateur',
                'name' => self::describeBrowser((string) $session->user_agent),
                'last_active_at' => Carbon::createFromTimestamp($session->last_activity),
                'signed_in_at' => null,
                'current' => $session->id === $currentSessionId,
            ])
            : collect();

        return $tokens->concat($sessions)->sortByDesc('last_active_at')->values();
    }

    /**
     * Déconnecte un appareil du membre. Renvoie faux s'il ne lui appartient pas.
     */
    public function revoke(User $user, string $deviceId): bool
    {
        if (str_starts_with($deviceId, 'app-')) {
            return $user->tokens()->whereKey((int) substr($deviceId, 4))->delete() > 0;
        }

        if (str_starts_with($deviceId, 'web-') && $this->sessionsTable()) {
            $hash = substr($deviceId, 4);
            $sessionId = DB::table('sessions')->where('user_id', $user->id)->pluck('id')
                ->first(fn (string $id) => hash_equals(hash('sha256', $id), $hash));

            return $sessionId !== null && DB::table('sessions')->where('id', $sessionId)->delete() > 0;
        }

        return false;
    }

    /**
     * Déconnecte tous les appareils sauf celui qui fait la demande.
     */
    public function revokeOthers(User $user, ?int $currentTokenId = null, ?string $currentSessionId = null): int
    {
        $count = $user->tokens()->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))->delete();

        if ($this->sessionsTable()) {
            $count += DB::table('sessions')->where('user_id', $user->id)
                ->when($currentSessionId, fn ($query) => $query->where('id', '!=', $currentSessionId))
                ->delete();
        }

        return $count;
    }

    /**
     * « Chrome sur Android », « Safari sur iPhone »… à partir de l'en-tête du navigateur.
     */
    public static function describeBrowser(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Navigateur',
        };

        $system = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') => 'iPhone',
            str_contains($userAgent, 'iPad') => 'iPad',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') => 'Mac',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $system ? "{$browser} sur {$system}" : $browser;
    }

    private function sessionsTable(): bool
    {
        return config('session.driver') === 'database';
    }
}
