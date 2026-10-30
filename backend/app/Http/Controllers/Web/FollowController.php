<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Sector;
use App\Models\User;
use App\Services\Network\FollowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(private readonly FollowService $follows) {}

    public function project(Request $request, Project $project): RedirectResponse
    {
        return $this->toggle($request, $project, 'ce projet');
    }

    public function member(Request $request, User $user): RedirectResponse
    {
        return $this->toggle($request, $user, $user->name);
    }

    public function sector(Request $request, Sector $sector): RedirectResponse
    {
        return $this->toggle($request, $sector, 'le secteur '.$sector->name);
    }

    private function toggle(Request $request, Project|User|Sector $target, string $name): RedirectResponse
    {
        $following = $this->follows->toggle($request->user(), $target);

        return back()->with('status', $following
            ? "Vous suivez {$name} : son activité apparaît dans l'onglet « Suivis »."
            : "Vous ne suivez plus {$name}.");
    }
}
