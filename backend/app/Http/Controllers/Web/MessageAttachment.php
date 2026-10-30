<?php

namespace App\Http\Controllers\Web;

use App\Enums\MessageKind;
use App\Models\Message;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Envoi d'une pièce jointe : les vocaux, photos et PDF s'ouvrent dans le
 * navigateur, les autres documents sont téléchargés.
 */
final class MessageAttachment
{
    private const INLINE = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public static function respond(Message $message): StreamedResponse
    {
        $disk = Storage::disk('local');
        $headers = ['X-Content-Type-Options' => 'nosniff'];

        if ($message->kind === MessageKind::Vocal || in_array($message->attachment_mime, self::INLINE, true)) {
            return $disk->response($message->attachment_path, $message->attachment_name, $headers);
        }

        return $disk->download($message->attachment_path, $message->attachment_name, $headers);
    }
}
