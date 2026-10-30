<?php

namespace App\Services\ContactRequests;

use App\Enums\ContactRequestStatus;
use App\Models\User;

/**
 * Indicateur de réactivité affiché sur chaque profil (MR-06) : part des
 * demandes reçues qui ont obtenu une réponse, et délai médian de réponse.
 */
class ReactivityIndicator
{
    /**
     * @return array{response_rate: int|null, median_response_hours: int|null, requests_considered: int}
     */
    public function for(User $user): array
    {
        $requests = $user->receivedContactRequests()
            ->where('created_at', '>=', now()->subDays(config('idee.reactivity_window_days')))
            ->whereNotIn('status', [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue])
            ->get(['status', 'created_at', 'responded_at']);

        if ($requests->isEmpty()) {
            return ['response_rate' => null, 'median_response_hours' => null, 'requests_considered' => 0];
        }

        $answered = $requests->filter(fn ($request) => $request->status->isAnswered());

        $median = $answered->isEmpty()
            ? null
            : (int) round($answered
                ->map(fn ($request) => $request->created_at->diffInMinutes($request->responded_at) / 60)
                ->median());

        return [
            'response_rate' => (int) round($answered->count() / $requests->count() * 100),
            'median_response_hours' => $median,
            'requests_considered' => $requests->count(),
        ];
    }
}
