<?php

namespace App\Services\Projects;

use App\Enums\AgreementStatus;
use App\Enums\MeetingStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\ProjectTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * EQ-03 : ce qui attend le projet, en un seul calendrier : tâches à échéance,
 * étapes convenues avec des partenaires, rendez-vous confirmés.
 */
class ProjectCalendar
{
    /**
     * @return Collection<string, Collection<int, array{date: Carbon, kind: string, title: string, detail: ?string, late: bool}>> par mois
     */
    public function upcoming(Project $project): Collection
    {
        $tasks = ProjectTask::where('project_id', $project->id)
            ->whereNotNull('due_on')->where('status', '!=', TaskStatus::Fait)
            ->with('assignee')->get()
            ->map(fn (ProjectTask $task) => [
                'date' => $task->due_on,
                'kind' => 'Tâche',
                'title' => $task->title,
                'detail' => $task->assignee ? 'confiée à '.$task->assignee->name : null,
                'late' => $task->due_on->isBefore(today()),
            ]);

        $agreements = Agreement::where('project_id', $project->id)
            ->where('status', AgreementStatus::Acceptee)
            ->with('owner')->get()
            ->map(fn (Agreement $agreement) => [
                'date' => $agreement->due_on,
                'kind' => 'Étape convenue',
                'title' => $agreement->objective,
                'detail' => 'responsable : '.$agreement->owner?->name,
                'late' => $agreement->due_on->isBefore(today()),
            ]);

        $meetings = Meeting::whereHas('contactRequest', fn ($query) => $query->where('project_id', $project->id))
            ->where('status', MeetingStatus::Confirmee)
            ->where('starts_at', '>=', now()->startOfDay())
            ->get()
            ->map(fn (Meeting $meeting) => [
                'date' => $meeting->starts_at,
                'kind' => 'Rendez-vous',
                'title' => $meeting->agenda ? str($meeting->agenda)->limit(80)->toString() : 'Rendez-vous',
                'detail' => $meeting->starts_at->format('H\hi').' · '.($meeting->place ?: $meeting->mode->label()),
                'late' => false,
            ]);

        return $tasks->concat($agreements)->concat($meetings)
            ->sortBy(fn (array $item) => $item['date']->timestamp)
            ->values()
            ->groupBy(fn (array $item) => ucfirst($item['date']->translatedFormat('F Y')));
    }
}
