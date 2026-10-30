<?php

namespace App\Services\Messaging;

use App\Enums\MessageKind;
use App\Models\ContactRequest;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Messagerie après acceptation d'une demande de contact : texte, vocal et documents (MR-04).
 */
class MessageService
{
    /**
     * @throws ValidationException
     */
    public function send(ContactRequest $conversation, User $sender, ?string $body, ?UploadedFile $attachment = null): Message
    {
        if (! $conversation->involves($sender) || ! $conversation->allowsMessages()) {
            throw ValidationException::withMessages([
                'body' => 'La messagerie s\'ouvre quand les deux personnes se sont dites intéressées.',
            ]);
        }

        $message = new Message;
        $message->contactRequest()->associate($conversation);
        $message->sender()->associate($sender);
        $message->body = filled($body) ? trim($body) : null;
        $message->kind = MessageKind::Texte;

        if ($attachment !== null) {
            $message->kind = $this->isVoice($attachment) ? MessageKind::Vocal : MessageKind::Document;
            $message->forceFill([
                'attachment_path' => $attachment->store("messages/{$conversation->id}", 'local'),
                'attachment_name' => Str::limit($attachment->getClientOriginalName(), 200, ''),
                'attachment_mime' => $attachment->getMimeType(),
                'attachment_size' => $attachment->getSize(),
            ]);
        }

        $message->save();

        $recipient = $conversation->sender_id === $sender->id ? $conversation->recipient : $conversation->sender;
        $recipient?->notify(new NewMessage($message));

        return $message;
    }

    public function isVoice(UploadedFile $file): bool
    {
        return str_starts_with((string) $file->getMimeType(), 'audio/')
            || in_array(strtolower($file->getClientOriginalExtension()), ['m4a', 'aac', 'ogg', 'oga', 'opus', 'amr', 'wav', 'mp3'], true);
    }

    /**
     * Marque comme lus les messages reçus dans la conversation.
     */
    public function markRead(ContactRequest $conversation, User $reader): int
    {
        return $conversation->messages()
            ->where('sender_id', '!=', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Messages reçus et pas encore lus, toutes conversations confondues.
     */
    public function unreadCount(User $user): int
    {
        return Message::query()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->whereHas('contactRequest', fn (Builder $query) => $query
                ->where('sender_id', $user->id)
                ->orWhere('recipient_id', $user->id))
            ->count();
    }
}
