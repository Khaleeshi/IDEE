<?php

namespace App\Services\Members;

use App\Enums\ContactRequestStatus;
use App\Enums\ModerationDecision;
use App\Enums\OrganisationRole;
use App\Enums\UserRole;
use App\Enums\VerificationLevel;
use App\Models\Agreement;
use App\Models\AssistantDocument;
use App\Models\BusinessPlan;
use App\Models\Collaboration;
use App\Models\ContactRequest;
use App\Models\DocumentAccess;
use App\Models\EventRegistration;
use App\Models\Follow;
use App\Models\GroupPost;
use App\Models\IdeaClarification;
use App\Models\Meeting;
use App\Models\Message;
use App\Models\OneTimeCode;
use App\Models\OpportunityApplication;
use App\Models\OrganisationMember;
use App\Models\Payment;
use App\Models\ProgrammeApplication;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\QuoteRequest;
use App\Models\Report;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Droits des membres sur leurs données (section 10 et critère de recette 8) :
 * export de tout ce qu'IDEE garde sur eux, puis suppression du compte.
 */
class AccountService
{
    /**
     * Mot à écrire pour confirmer la suppression : on ne supprime pas un compte par un clic égaré.
     */
    public const CONFIRMATION = 'SUPPRIMER';

    /**
     * Colonnes techniques sans intérêt pour le membre.
     */
    private const TECHNICAL = ['id', 'model', 'input_tokens', 'output_tokens', 'error', 'provider_reference', 'launch_url', 'priority', 'removed_content', 'reportable_type'];

    /**
     * Toutes les données du membre, lisibles par une personne comme par une machine.
     * Des autres membres, l'export ne donne que le nom : jamais leur téléphone ni leur e-mail.
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $user->loadMissing('roleAssignments');
        $involves = fn (string $first, string $second) => fn (Builder $query) => $query->where($first, $user->id)->orWhere($second, $user->id);

        return [
            'exporte_le' => now()->toIso8601String(),
            'profil' => [
                'nom' => $user->name,
                'telephone' => $user->phone,
                'email' => $user->email,
                'region' => $user->region,
                'langues' => $user->languages,
                'presentation' => $user->bio,
                'competences' => $user->skills,
                'secteurs' => $user->sectors,
                'services_d_expert' => $user->expertise,
                'tarif' => $user->rate,
                'disponibilite' => $user->availability?->label(),
                'preferences_de_contact' => $user->contact_preferences,
                'roles' => array_map(fn (UserRole $role) => $role->label(), $user->roles()),
                'verification' => $user->verification_level->label(),
                'inscrit_le' => $user->created_at?->toIso8601String(),
            ],
            'projets' => $user->projects()->withTrashed()->with(['sector', 'needs', 'updates', 'team', 'documents', 'versions'])->oldest()->get()
                ->map(fn (Project $project) => [
                    ...$this->record($project),
                    'secteur' => $project->sector?->name,
                    'besoins' => $this->records($project->needs),
                    'mises_a_jour' => $this->records($project->updates),
                    'equipe' => $this->records($project->team),
                    'documents' => $project->documents->map(fn ($document) => [
                        'titre' => $document->title,
                        'fichier' => $document->original_name,
                        'depose_le' => $document->created_at?->toIso8601String(),
                    ])->all(),
                    'versions_horodatees' => $project->versions->map(fn ($version) => [
                        'empreinte' => $version->fingerprint,
                        'date' => $version->created_at?->toIso8601String(),
                    ])->all(),
                    'business_plans' => $this->records(BusinessPlan::where('project_id', $project->id)->get()),
                    'echanges_avec_l_assistant' => $this->records(IdeaClarification::where('project_id', $project->id)->get()),
                ])->all(),
            'demandes_de_contact' => ContactRequest::where($involves('sender_id', 'recipient_id'))
                ->with(['sender', 'recipient', 'project', 'need', 'messages', 'agreements', 'meetings'])->oldest()->get()
                ->map(fn (ContactRequest $request) => [
                    'sens' => $request->sender_id === $user->id ? 'envoyée' : 'reçue',
                    'interlocuteur' => $request->otherParty($user)?->name,
                    'projet' => $request->project?->title,
                    'besoin' => $request->need?->title,
                    'message' => $request->message,
                    'statut' => $request->status->label(),
                    'motif_de_refus' => $request->refusal_reason,
                    'date' => $request->created_at?->toIso8601String(),
                    'messages' => $request->messages->map(fn (Message $message) => [
                        'de' => $message->sender_id === $user->id ? 'moi' : $request->otherParty($user)?->name,
                        'texte' => $message->isRemoved() ? '(message retiré par la modération)' : $message->body,
                        'piece_jointe' => $message->attachment_name,
                        'date' => $message->created_at?->toIso8601String(),
                    ])->all(),
                    'accords' => $this->records($request->agreements),
                    'rendez_vous' => $this->records($request->meetings),
                ])->all(),
            'aides_proposees' => $user->helpOffers()->with('need')->get()
                ->map(fn ($offer) => [...$this->record($offer), 'besoin' => $offer->need?->title])->all(),
            'abonnements' => Follow::where('user_id', $user->id)->get()->map(fn (Follow $follow) => array_filter([
                'projet' => $follow->project_id ? Project::withTrashed()->find($follow->project_id)?->title : null,
                'membre' => $follow->followed_id ? User::withTrashed()->find($follow->followed_id)?->name : null,
                'secteur' => $follow->sector_id ? Sector::find($follow->sector_id)?->name : null,
            ]))->all(),
            'parcours' => $user->stepProgress()->with('step')->get()
                ->map(fn ($progress) => [...$this->record($progress), 'etape' => $progress->step?->title])->all(),
            'alertes' => $this->records($user->savedSearches),
            'suivi_des_opportunites' => OpportunityApplication::whereBelongsTo($user)->with('opportunity')->get()
                ->map(fn ($application) => [...$this->record($application), 'opportunite' => $application->opportunity?->title])->all(),
            'candidatures_aux_programmes' => ProgrammeApplication::whereBelongsTo($user)->with('opportunity')->get()
                ->map(fn ($application) => [...$this->record($application), 'programme' => $application->opportunity?->title])->all(),
            'evenements' => EventRegistration::whereBelongsTo($user)->with('event')->get()
                ->map(fn ($registration) => [...$this->record($registration), 'evenement' => $registration->event?->title])->all(),
            'accords_de_prochaine_etape' => $this->records(Agreement::where($involves('proposer_id', 'owner_id'))->get()),
            'taches' => $this->records(ProjectTask::where($involves('assignee_id', 'created_by'))->get()),
            'collaborations' => $this->records(Collaboration::where($involves('declared_by', 'partner_id'))->get()),
            'rendez_vous_proposes' => $this->records(Meeting::where('proposer_id', $user->id)->get()),
            'devis' => QuoteRequest::where($involves('client_id', 'expert_id'))->get()
                ->map(fn (QuoteRequest $quote) => [...$this->record($quote), 'mon_role' => $quote->client_id === $user->id ? 'client' : 'expert'])->all(),
            'organisations' => $user->organisationMemberships()->with('organisation')->get()
                ->map(fn (OrganisationMember $member) => ['organisation' => $member->organisation?->name, 'role' => $member->role->label()])->all(),
            'paiements' => $this->records(Payment::whereBelongsTo($user)->get()),
            'offres' => $this->records($user->entitlements),
            'verifications' => $this->records($user->verifications),
            'documents_de_l_assistant' => $this->records(AssistantDocument::whereBelongsTo($user)->get()),
            'publications_dans_les_groupes' => $this->records(GroupPost::whereBelongsTo($user)->get()),
            'signalements_envoyes' => Report::where('reporter_id', $user->id)->get()->map(fn (Report $report) => [
                'motif' => $report->reason->label(),
                'details' => $report->details,
                'statut' => $report->status->label(),
                'date' => $report->created_at?->toIso8601String(),
            ])->all(),
            'decisions_de_moderation_me_concernant' => Report::where('target_user_id', $user->id)
                ->whereNotNull('decision')
                ->where('decision', '!=', ModerationDecision::AucuneAction)
                ->get()
                ->map(fn (Report $report) => [
                    'decision' => $report->decision->label(),
                    'motif' => $report->decision_note,
                    'date' => $report->decided_at?->toIso8601String(),
                ])->all(),
            'notifications' => $user->notifications()->latest()->get()->map(fn ($notification) => [
                'contenu' => $notification->data,
                'recue_le' => $notification->created_at?->toIso8601String(),
                'lue_le' => $notification->read_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Supprime le compte : projets, fichiers et données personnelles sont effacés.
     * Restent, sous le nom « Membre supprimé », ce qui appartient aussi aux autres
     * membres (messages de leurs conversations, accords, collaborations), les
     * paiements et reçus (obligation comptable) et le journal de modération.
     */
    public function delete(User $user): void
    {
        $projects = $user->projects()->withTrashed()->with('documents')->get();
        $files = [
            'local' => [
                ...$projects->flatMap(fn (Project $project) => $project->documents->pluck('path'))->all(),
                ...$user->verifications()->whereNotNull('document_path')->pluck('document_path')->all(),
            ],
            'public' => array_filter([$user->photo_path]),
        ];

        DB::transaction(function () use ($user, $projects) {
            $projects->each(fn (Project $project) => $project->forceDelete());

            // Personne ne reste sans réponse : les demandes en attente sont closes (RG-03).
            ContactRequest::where(fn (Builder $query) => $query->where('sender_id', $user->id)->orWhere('recipient_id', $user->id))
                ->whereIn('status', [ContactRequestStatus::Envoyee, ContactRequestStatus::Lue])
                ->get()
                ->each(fn (ContactRequest $request) => $request->transitionTo(ContactRequestStatus::Expiree));

            $this->handOverOrganisations($user);

            $user->tokens()->delete();
            $user->pushSubscriptions()->delete();
            $user->roleAssignments()->delete();
            $user->savedSearches()->delete();
            $user->stepProgress()->delete();
            $user->helpOffers()->delete();
            $user->verifications()->delete();
            $user->entitlements()->delete();
            $user->notifications()->delete();
            Follow::where('user_id', $user->id)->orWhere('followed_id', $user->id)->delete();
            OpportunityApplication::whereBelongsTo($user)->delete();
            EventRegistration::whereBelongsTo($user)->delete();
            DocumentAccess::whereBelongsTo($user)->delete();
            AssistantDocument::whereBelongsTo($user)->delete();
            GroupPost::whereBelongsTo($user)->delete();
            OneTimeCode::whereIn('identifier', array_filter([$user->phone, $user->email]))->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => 'Membre supprimé',
                'phone' => null,
                'phone_verified_at' => null,
                'email' => null,
                'email_verified_at' => null,
                'password' => null,
                'remember_token' => null,
                'region' => null,
                'languages' => null,
                'bio' => null,
                'expertise' => null,
                'rate' => null,
                'skills' => null,
                'sectors' => null,
                'contact_preferences' => null,
                'availability' => null,
                'photo_path' => null,
                'verification_level' => VerificationLevel::None,
                'is_admin' => false,
            ])->save();

            $user->delete();
        });

        foreach ($files as $disk => $paths) {
            Storage::disk($disk)->delete($paths);
        }
    }

    /**
     * Une organisation ne reste pas sans personne pour gérer sa page.
     */
    private function handOverOrganisations(User $user): void
    {
        $memberships = $user->organisationMemberships()->get();

        foreach ($memberships as $membership) {
            $otherAdmins = OrganisationMember::where('organisation_id', $membership->organisation_id)
                ->where('user_id', '!=', $user->id)
                ->where('role', OrganisationRole::Administrateur)
                ->exists();

            if ($membership->role === OrganisationRole::Administrateur && ! $otherAdmins) {
                OrganisationMember::where('organisation_id', $membership->organisation_id)
                    ->where('user_id', '!=', $user->id)
                    ->oldest()
                    ->first()
                    ?->forceFill(['role' => OrganisationRole::Administrateur])
                    ->save();
            }

            $membership->delete();
        }
    }

    /**
     * Colonnes d'un enregistrement, sans les clés et champs techniques.
     *
     * @return array<string, mixed>
     */
    private function record(Model $model): array
    {
        return collect($model->attributesToArray())
            ->reject(fn ($value, string $key) => in_array($key, self::TECHNICAL, true)
                || str_ends_with($key, '_id')
                || str_ends_with($key, '_path')
                || in_array($key, ['created_by', 'declared_by'], true))
            ->all();
    }

    /**
     * @param  iterable<Model>  $models
     * @return list<array<string, mixed>>
     */
    private function records(iterable $models): array
    {
        $records = [];

        foreach ($models as $model) {
            $records[] = $this->record($model);
        }

        return $records;
    }
}
