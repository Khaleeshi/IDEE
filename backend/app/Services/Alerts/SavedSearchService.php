<?php

namespace App\Services\Alerts;

use App\Enums\NeedType;
use App\Enums\ProjectStage;
use App\Models\Project;
use App\Models\SavedSearch;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\SavedSearchMatches;
use App\Services\Projects\ProjectSearch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Alertes enregistrées sur de nouveaux projets (RR-04) : une recherche du
 * fil, vérifiée chaque jour.
 */
class SavedSearchService
{
    public function __construct(private readonly ProjectSearch $search) {}

    /**
     * @param  array<string, mixed>  $filters
     *
     * @throws ValidationException
     */
    public function save(User $user, array $filters): SavedSearch
    {
        $filters = $this->normalize($filters);

        if ($filters === []) {
            throw ValidationException::withMessages(['filters' => 'Choisissez au moins un filtre ou un mot-clé avant de créer une alerte.']);
        }

        $existing = $user->savedSearches()->get()->first(fn (SavedSearch $search) => $search->filters == $filters);

        if ($existing !== null) {
            return $existing;
        }

        if ($user->savedSearches()->count() >= config('idee.alerts.max_saved_searches')) {
            throw ValidationException::withMessages(['filters' => 'Vous avez atteint le nombre maximum d\'alertes. Supprimez-en une pour en créer une autre.']);
        }

        $search = new SavedSearch(['label' => $this->label($filters), 'filters' => $filters]);
        $search->user()->associate($user);
        $search->checked_at = now();
        $search->save();

        return $search;
    }

    /**
     * Projets publiés depuis une date qui correspondent à l'alerte, hors projets du membre.
     *
     * @return Collection<int, Project>
     */
    public function newMatches(SavedSearch $search, Carbon $since): Collection
    {
        return $this->search->query($search->filters)
            ->where('created_at', '>', $since)
            ->where('owner_id', '!=', $search->user_id)
            ->get();
    }

    /**
     * Prévient chaque membre des nouveaux projets de ses alertes.
     */
    public function sendAlerts(): int
    {
        $sent = 0;

        SavedSearch::query()->with('user')->whereHas('user')->each(function (SavedSearch $search) use (&$sent) {
            $checkedAt = now();
            $matches = $this->newMatches($search, $search->checked_at ?? $search->created_at);

            if ($matches->isNotEmpty() && ! $search->user->isRestricted()) {
                $search->user->notify(new SavedSearchMatches($search, $matches->count()));
                $sent++;
            }

            $search->forceFill(['checked_at' => $checkedAt])->save();
        });

        return $sent;
    }

    /**
     * Garde les filtres connus et non vides, dans un ordre stable.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    public function normalize(array $filters): array
    {
        $kept = [];

        foreach (ProjectSearch::FILTERS as $key) {
            $value = trim((string) ($filters[$key] ?? ''));

            if ($value !== '' && (! str_starts_with($key, 'amount_') || is_numeric($value))) {
                $kept[$key] = mb_substr($value, 0, 100);
            }
        }

        return $kept;
    }

    /**
     * Libellé lisible : « séchoir » · Agroalimentaire · Thiès · Financement.
     *
     * @param  array<string, string>  $filters
     */
    public function label(array $filters): string
    {
        $parts = array_filter([
            isset($filters['q']) ? '« '.$filters['q'].' »' : null,
            isset($filters['sector']) ? Sector::firstWhere('slug', $filters['sector'])?->name : null,
            $filters['region'] ?? null,
            isset($filters['stage']) ? ProjectStage::tryFrom($filters['stage'])?->label() : null,
            isset($filters['need_type']) ? NeedType::tryFrom($filters['need_type'])?->label() : null,
            isset($filters['amount_min']) ? 'dès '.$this->fcfa($filters['amount_min']) : null,
            isset($filters['amount_max']) ? 'jusqu\'à '.$this->fcfa($filters['amount_max']) : null,
        ]);

        return mb_substr(implode(' · ', $parts), 0, 200);
    }

    private function fcfa(string $amount): string
    {
        return number_format((int) $amount, 0, ',', "\u{00A0}").' FCFA';
    }
}
