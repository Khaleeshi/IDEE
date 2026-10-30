<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * De nouveaux projets correspondent à une alerte enregistrée (RR-04).
 */
class SavedSearchMatches extends Notification
{
    use Queueable;

    public function __construct(
        public readonly SavedSearch $search,
        public readonly int $count,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return AlertChannels::for($notifiable, whatsapp: false);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'saved_search_id' => $this->search->id,
            'message' => $this->count.' nouveau'.($this->count > 1 ? 'x projets correspondent' : ' projet correspond').' à votre alerte « '.$this->search->label.' ».',
        ];
    }
}
