<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\MessageAttachment;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\ContactRequest;
use App\Models\Message;
use App\Services\Messaging\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageController extends Controller
{
    public function __construct(private readonly MessageService $messages) {}

    /**
     * Messages d'une conversation ; ceux reçus sont marqués comme lus.
     */
    public function index(Request $request, ContactRequest $contactRequest): AnonymousResourceCollection
    {
        Gate::authorize('view', $contactRequest);

        $this->messages->markRead($contactRequest, $request->user());

        return MessageResource::collection($contactRequest->messages()->paginate(50));
    }

    public function store(StoreMessageRequest $request, ContactRequest $contactRequest): JsonResponse
    {
        Gate::authorize('message', $contactRequest);

        $message = $this->messages->send($contactRequest, $request->user(), $request->validated('body'), $request->file('attachment'));

        return (new MessageResource($message))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function attachment(Message $message): StreamedResponse
    {
        Gate::authorize('view', $message->contactRequest);
        abort_unless($message->hasAttachment(), Response::HTTP_NOT_FOUND);

        return MessageAttachment::respond($message);
    }
}
