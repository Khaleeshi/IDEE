<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Auth\DeviceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mes appareils connectés (CP-06).
 */
class DeviceController extends Controller
{
    public function __construct(private readonly DeviceService $devices) {}

    public function index(Request $request): View
    {
        return view('profile.devices', [
            'devices' => $this->devices->list($request->user(), null, $request->session()->getId()),
        ]);
    }

    public function destroy(Request $request, string $device): RedirectResponse
    {
        $revoked = $this->devices->revoke($request->user(), $device);

        return back()->with('status', $revoked ? 'Appareil déconnecté.' : 'Cet appareil n\'était déjà plus connecté.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $count = $this->devices->revokeOthers($request->user(), null, $request->session()->getId());

        return back()->with('status', $count > 0 ? "{$count} appareil(s) déconnecté(s). Vous restez connecté ici." : 'Aucun autre appareil n\'était connecté.');
    }
}
