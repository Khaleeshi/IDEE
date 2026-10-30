<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteAccountRequest;
use App\Services\Members\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Export des données et suppression du compte depuis le site (section 10).
 */
class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts) {}

    public function export(Request $request): JsonResponse
    {
        return response()->json($this->accounts->export($request->user()), options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="idee-mes-donnees-'.now()->format('Y-m-d').'.json"');
    }

    public function confirm(Request $request): View
    {
        return view('profile.delete', [
            'user' => $request->user(),
            'projects' => $request->user()->projects()->count(),
            'word' => AccountService::CONFIRMATION,
        ]);
    }

    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $this->accounts->delete($request->user());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('status', 'Votre compte et vos données ont été supprimés.')
            ->with('clear_offline_cache', true);
    }
}
