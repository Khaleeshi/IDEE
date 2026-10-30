<?php

namespace App\Services\Network;

use App\Models\Follow;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Suivre un projet, une personne ou un secteur (FC-02).
 */
class FollowService
{
    public function isFollowing(?User $user, Project|User|Sector $target): bool
    {
        return $user !== null && $this->query($user, $target)->exists();
    }

    /**
     * Suit la cible, ou cesse de la suivre si c'était déjà le cas.
     *
     * @return bool Vrai si le membre suit désormais la cible.
     *
     * @throws ValidationException
     */
    public function toggle(User $user, Project|User|Sector $target): bool
    {
        if ($target instanceof User && $target->is($user)) {
            throw ValidationException::withMessages(['follow' => 'Vous ne pouvez pas vous suivre vous-même.']);
        }

        $existing = $this->query($user, $target)->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        $follow = new Follow;
        $follow->user()->associate($user);
        $follow->forceFill([$this->column($target) => $target->getKey()])->save();

        return true;
    }

    /**
     * Identifiants suivis par le membre, par type.
     *
     * @return array{projects: list<string>, users: list<string>, sectors: list<int>}
     */
    public function followedIds(User $user): array
    {
        $follows = Follow::whereBelongsTo($user)->get(['project_id', 'followed_id', 'sector_id']);

        return [
            'projects' => $follows->pluck('project_id')->filter()->values()->all(),
            'users' => $follows->pluck('followed_id')->filter()->values()->all(),
            'sectors' => $follows->pluck('sector_id')->filter()->values()->all(),
        ];
    }

    /**
     * @return Builder<Follow>
     */
    private function query(User $user, Project|User|Sector $target): Builder
    {
        return Follow::whereBelongsTo($user)->where($this->column($target), $target->getKey());
    }

    private function column(Project|User|Sector $target): string
    {
        return match (true) {
            $target instanceof Project => 'project_id',
            $target instanceof User => 'followed_id',
            $target instanceof Sector => 'sector_id',
        };
    }
}
