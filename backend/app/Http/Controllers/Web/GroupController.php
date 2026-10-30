<?php

namespace App\Http\Controllers\Web;

use App\Enums\GroupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupPostRequest;
use App\Models\GroupPost;
use App\Models\Sector;
use App\Services\Groups\GroupService;
use App\Services\Network\FollowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Groupes par secteur ou par région (FC-05).
 */
class GroupController extends Controller
{
    public function __construct(private readonly GroupService $groups) {}

    public function index(): View
    {
        return view('groups.index', ['groups' => $this->groups->all()]);
    }

    public function show(Request $request, GroupType $type, string $key, FollowService $follows): View
    {
        $name = $this->groups->name($type, $key);
        abort_if($name === null, 404);
        $sector = $type === GroupType::Secteur ? Sector::firstWhere('slug', $key) : null;

        return view('groups.show', [
            'type' => $type,
            'key' => $key,
            'name' => $name,
            'projects' => $this->groups->projects($type, $key)->limit(5)->get(),
            'projectsCount' => $this->groups->projects($type, $key)->count(),
            'posts' => $this->groups->posts($type, $key)->paginate(30),
            'sector' => $sector,
            'following' => $sector ? $follows->isFollowing($request->user(), $sector) : false,
        ]);
    }

    public function store(StoreGroupPostRequest $request, GroupType $type, string $key): RedirectResponse
    {
        abort_if($this->groups->name($type, $key) === null, 404);

        $this->groups->post($request->user(), $type, $key, $request->validated('body'));

        return redirect()->to(route('groups.show', [$type, $key]).'#discussion')->with('status', 'Message publié.');
    }

    public function destroy(Request $request, GroupPost $groupPost): RedirectResponse
    {
        abort_unless($groupPost->user_id === $request->user()->id, 403);

        $groupPost->delete();

        return redirect()->to(route('groups.show', [$groupPost->group_type, $groupPost->group_key]).'#discussion')->with('status', 'Message supprimé.');
    }
}
