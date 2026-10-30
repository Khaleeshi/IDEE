<?php

namespace App\Services\Groups;

use App\Enums\GroupType;
use App\Models\GroupPost;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Support\Regions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Groupes par secteur ou par région (FC-05) : les projets du groupe et un
 * fil de discussion entre ses membres.
 */
class GroupService
{
    /**
     * Nom du groupe, ou null s'il n'existe pas.
     */
    public function name(GroupType $type, string $key): ?string
    {
        return match ($type) {
            GroupType::Secteur => Sector::firstWhere('slug', $key)?->name,
            GroupType::Region => Regions::fromSlug($key),
        };
    }

    /**
     * Tous les groupes, avec le nombre de projets actifs de chacun.
     *
     * @return array<string, Collection<int, array{type: GroupType, key: string, name: string, projects: int}>>
     */
    public function all(): array
    {
        $bySector = Project::active()->selectRaw('sector_id, count(*) as total')->groupBy('sector_id')->pluck('total', 'sector_id');
        $byRegion = Project::active()->pluck('region')->countBy(fn (?string $region) => Str::slug((string) $region));

        return [
            GroupType::Secteur->value => Sector::orderBy('name')->get()->map(fn (Sector $sector) => [
                'type' => GroupType::Secteur, 'key' => $sector->slug, 'name' => $sector->name, 'projects' => (int) ($bySector[$sector->id] ?? 0),
            ]),
            GroupType::Region->value => collect(Regions::ALL)->map(fn (string $region) => [
                'type' => GroupType::Region, 'key' => Str::slug($region), 'name' => $region, 'projects' => (int) ($byRegion[Str::slug($region)] ?? 0),
            ]),
        ];
    }

    /**
     * Projets actifs du groupe, les plus récemment mis à jour d'abord.
     *
     * @return Builder<Project>
     */
    public function projects(GroupType $type, string $key): Builder
    {
        return Project::query()
            ->active()
            ->when($type === GroupType::Secteur, fn (Builder $query) => $query->whereHas('sector', fn (Builder $query) => $query->where('slug', $key)))
            ->when($type === GroupType::Region, fn (Builder $query) => $query->where('region', Regions::fromSlug($key)))
            ->with(['sector', 'openNeeds'])
            ->latest('last_activity_at');
    }

    /**
     * @return Builder<GroupPost>
     */
    public function posts(GroupType $type, string $key): Builder
    {
        return GroupPost::query()
            ->where('group_type', $type)
            ->where('group_key', $key)
            ->whereNull('removed_at')
            ->with('user')
            ->latest()
            ->latest('id');
    }

    /**
     * @throws ValidationException
     */
    public function post(User $author, GroupType $type, string $key, string $body): GroupPost
    {
        if ($this->name($type, $key) === null) {
            throw ValidationException::withMessages(['body' => 'Ce groupe n\'existe pas.']);
        }

        $today = GroupPost::whereBelongsTo($author)->where('created_at', '>=', now()->subDay())->count();

        if ($today >= config('idee.groups.posts_per_day')) {
            throw ValidationException::withMessages(['body' => 'Vous avez atteint la limite de '.config('idee.groups.posts_per_day').' messages par jour dans les groupes.']);
        }

        $post = new GroupPost(['body' => trim($body)]);
        $post->forceFill(['group_type' => $type, 'group_key' => $key]);
        $post->user()->associate($author);
        $post->save();

        return $post;
    }
}
