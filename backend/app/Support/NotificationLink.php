<?php

namespace App\Support;

/**
 * Page d'IDEE vers laquelle mène une notification, d'après ses données.
 */
final class NotificationLink
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function for(array $data): ?string
    {
        return match (true) {
            isset($data['contact_request_id']) => route('contact-requests.show', $data['contact_request_id']),
            isset($data['journey_step_id']) => route('journeys.index'),
            isset($data['saved_search_id']) => route('alerts.show', $data['saved_search_id']),
            isset($data['opportunity_id']) => route('opportunities.show', $data['opportunity_id']),
            isset($data['report_id']) => route('moderation.decision', $data['report_id']),
            isset($data['verification_id']) => route('profile.edit').'#verification',
            isset($data['project_id']) => route('projects.show', $data['project_id']).'#documents',
            default => null,
        };
    }
}
