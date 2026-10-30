<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Models\ContactRequest;
use App\Models\Message;
use App\Services\Messaging\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    public function store(StoreMessageRequest $request, ContactRequest $contactRequest, MessageService $messages): RedirectResponse
    {
        Gate::authorize('message', $contactRequest);

        $messages->send($contactRequest, $request->user(), $request->validated('body'), $request->file('attachment'));

        return redirect()->to(route('contact-requests.show', $contactRequest).'#messages');
    }

    /**
     * Pièce jointe, réservée aux deux personnes de la conversation.
     */
    public function attachment(Message $message): StreamedResponse
    {
        Gate::authorize('view', $message->contactRequest);
        abort_unless($message->hasAttachment(), 404);

        return MessageAttachment::respond($message);
    }
}
