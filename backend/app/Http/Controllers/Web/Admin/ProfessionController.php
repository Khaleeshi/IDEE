<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Référentiel des métiers administré par l'équipe IDEE (PM-01) : on ajoute un
 * métier ou une catégorie sans toucher au code. Un métier ne se supprime pas,
 * il se désactive, pour ne pas casser les profils qui l'utilisent (PM-04).
 */
class ProfessionController extends Controller
{
    public function index(): View
    {
        return view('admin.professions', [
            'categories' => ProfessionCategory::query()->orderBy('position')
                ->with(['professions' => fn ($query) => $query->withCount('memberProfiles')])
                ->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']], ['name.required' => 'Donnez un nom à la catégorie.']);
        $slug = Str::slug($data['name']);

        if (ProfessionCategory::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Cette catégorie existe déjà.'])->withInput();
        }

        ProfessionCategory::create(['slug' => $slug, 'name' => $data['name'], 'position' => (int) ProfessionCategory::max('position') + 10]);

        return back()->with('status', 'Catégorie ajoutée.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $slug = Str::slug($data['name']);

        if (Profession::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'Ce métier existe déjà dans la liste.'])->withInput();
        }

        Profession::create([...$data, 'slug' => $slug, 'active' => true]);

        return back()->with('status', 'Métier ajouté.');
    }

    public function edit(Profession $profession): View
    {
        return view('admin.profession', [
            'profession' => $profession->loadCount('memberProfiles'),
            'categories' => ProfessionCategory::query()->orderBy('position')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, Profession $profession): RedirectResponse
    {
        // Le lien (slug) ne change pas : seul le nom affiché change.
        $profession->update([...$this->validated($request), 'active' => $request->boolean('active')]);

        return redirect()->route('admin.professions')->with('status', 'Métier enregistré.');
    }

    /**
     * @return array{profession_category_id: int, name: string, description: ?string, regulated: bool}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'profession_category_id' => ['required', 'integer', Rule::exists('profession_categories', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
        ], [
            'profession_category_id.required' => 'Choisissez une catégorie.',
            'name.required' => 'Donnez un nom au métier.',
        ]);

        return [...$data, 'regulated' => $request->boolean('regulated')];
    }
}
