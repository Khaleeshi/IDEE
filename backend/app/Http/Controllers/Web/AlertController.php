<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use App\Services\Alerts\SavedSearchService;
use App\Services\Projects\ProjectSearch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Alertes enregistrées sur de nouveaux projets (RR-04).
 */
class AlertController extends Controller
{
    public function __construct(private readonly SavedSearchService $searches) {}

    public function index(Request $request): View
    {
        return view('alerts.index', [
            'alerts' => $request->user()->savedSearches,
            'max' => $request->user()->savedSearchLimit(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $search = $this->searches->save($request->user(), $request->only(ProjectSearch::FILTERS));

        return redirect()->to($search->url())->with('status', 'Alerte enregistrée : vous serez prévenu des nouveaux projets de cette recherche.');
    }

    /**
     * Ouvre les résultats d'une alerte, depuis une notification.
     */
    public function show(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 404);

        return redirect()->to($savedSearch->url());
    }

    public function destroy(Request $request, SavedSearch $savedSearch): RedirectResponse
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 404);

        $savedSearch->delete();

        return redirect()->route('alerts.index')->with('status', 'Alerte supprimée.');
    }
}
