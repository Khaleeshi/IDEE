<?php

namespace App\Http\Controllers\Api;

use App\Enums\GroupType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupPostRequest;
use App\Http\Resources\EnumResource;
use App\Http\Resources\ProjectResource;
use App\Models\GroupPost;
use App\Services\Groups\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Groupes par secteur ou par région (FC-05).
 */
class GroupController extends Controller
{
    public function __construct(private readonly GroupService $groups) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => collect($this->groups->all())->map(fn ($groups) => $groups->map(fn (array $group) => [
            ...$group,
            'type' => EnumResource::make($group['type']),
        ]))]);
    }

    /**
     * Le groupe, ses projets récents et ses derniers messages.
     */
    public function show(GroupType $type, string $key): JsonResponse
    {
        $name = $this->groups->name($type, $key);
        abort_if($name === null, Response::HTTP_NOT_FOUND);

        $posts = $this->groups->posts($type, $key)->paginate(30);

        return response()->json([
            'data' => [
                'type' => EnumResource::make($type),
                'key' => $key,
                'name' => $name,
                'projects' => ProjectResource::collection($this->groups->projects($type, $key)->with('owner.roleAssignments')->limit(10)->get()),
                'posts' => collect($posts->items())->map($this->post(...)),
            ],
            'meta' => ['current_page' => $posts->currentPage(), 'last_page' => $posts->lastPage()],
        ]);
    }

    public function store(StoreGroupPostRequest $request, GroupType $type, string $key): JsonResponse
    {
        abort_if($this->groups->name($type, $key) === null, Response::HTTP_NOT_FOUND);

        $post = $this->groups->post($request->user(), $type, $key, $request->validated('body'));

        return response()->json(['data' => $this->post($post->load('user'))], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, GroupPost $groupPost): Response
    {
        abort_unless($groupPost->user_id === $request->user()->id, Response::HTTP_FORBIDDEN);

        $groupPost->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function post(GroupPost $post): array
    {
        return [
            'id' => $post->id,
            'body' => $post->body,
            'author' => ['id' => $post->user_id, 'name' => $post->user?->name],
            'created_at' => $post->created_at?->toIso8601String(),
        ];
    }
}
