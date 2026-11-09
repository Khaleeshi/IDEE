<?php

namespace App\Services\Accompaniments;

use App\Enums\AccompanimentStatus;
use App\Enums\QuoteStatus;
use App\Models\Accompaniment;
use App\Models\QuoteRequest;
use App\Models\Review;
use App\Models\User;
use App\Notifications\AccompanimentNotice;
use App\Notifications\QuoteNotice;
use Illuminate\Validation\ValidationException;

/**
 * Avis sur les coachs, mentors et experts (AV-01 à AV-03). Un avis n'est
 * possible qu'après une relation réelle et terminée : pas de faux avis.
 */
class ReviewService
{
    /**
     * @throws ValidationException
     */
    public function forAccompaniment(Accompaniment $accompaniment, User $reviewer, int $rating, ?string $comment): Review
    {
        if ($reviewer->id !== $accompaniment->requester_id || $accompaniment->status !== AccompanimentStatus::Terminee) {
            throw ValidationException::withMessages(['rating' => 'Seule la personne accompagnée laisse un avis, une fois l\'accompagnement terminé.']);
        }

        $review = $this->store($reviewer, $accompaniment->coach, $rating, $comment, accompaniment: $accompaniment);
        $accompaniment->coach->notify(new AccompanimentNotice($accompaniment, $reviewer->name.' a laissé un avis sur votre accompagnement.'));

        return $review;
    }

    /**
     * @throws ValidationException
     */
    public function forQuote(QuoteRequest $quote, User $reviewer, int $rating, ?string $comment): Review
    {
        if ($reviewer->id !== $quote->client_id || $quote->status !== QuoteStatus::Terminee) {
            throw ValidationException::withMessages(['rating' => 'Seul le client laisse un avis, une fois la prestation confirmée terminée.']);
        }

        $review = $this->store($reviewer, $quote->expert, $rating, $comment, quote: $quote);
        $quote->expert->notify(new QuoteNotice($quote, $reviewer->name.' a laissé un avis sur votre prestation.'));

        return $review;
    }

    /**
     * AV-03 : la personne évaluée répond une fois, publiquement.
     *
     * @throws ValidationException
     */
    public function reply(Review $review, User $author, string $reply): Review
    {
        if ($author->id !== $review->reviewee_id || $review->reply !== null) {
            throw ValidationException::withMessages(['reply' => 'Seule la personne évaluée répond, une seule fois.']);
        }

        $review->forceFill(['reply' => trim($reply), 'replied_at' => now()])->save();

        return $review;
    }

    /**
     * Note moyenne et nombre d'avis reçus par un membre.
     *
     * @return array{average: ?float, count: int}
     */
    public function summary(User $user): array
    {
        $count = Review::where('reviewee_id', $user->id)->count();

        return ['average' => $count ? round((float) Review::where('reviewee_id', $user->id)->avg('rating'), 1) : null, 'count' => $count];
    }

    /**
     * @throws ValidationException
     */
    private function store(User $reviewer, User $reviewee, int $rating, ?string $comment, ?Accompaniment $accompaniment = null, ?QuoteRequest $quote = null): Review
    {
        if ($rating < 1 || $rating > 5) {
            throw ValidationException::withMessages(['rating' => 'Donnez une note de 1 à 5.']);
        }

        $exists = Review::query()
            ->when($accompaniment, fn ($query) => $query->where('accompaniment_id', $accompaniment->id))
            ->when($quote, fn ($query) => $query->where('quote_request_id', $quote->id))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['rating' => 'Vous avez déjà laissé un avis.']);
        }

        $review = new Review;
        $review->forceFill([
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $reviewee->id,
            'accompaniment_id' => $accompaniment?->id,
            'quote_request_id' => $quote?->id,
            'rating' => $rating,
            'comment' => filled($comment) ? trim($comment) : null,
        ])->save();

        return $review;
    }
}
