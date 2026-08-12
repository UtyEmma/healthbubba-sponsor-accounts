<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Http\Resources\WorkspaceActivityResource;
use App\Http\Resources\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Activity\WorkspaceActivityAuthorizationService;
use App\Services\Activity\WorkspaceActivityQuery;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly WorkspaceActivityAuthorizationService $activityAuthorization,
        private readonly WorkspaceActivityQuery $activities,
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $workspace = Workspace::current();
        $user = $request->user();
        $activityNotifications = null;

        if ($workspace instanceof Workspace
            && $user instanceof User
            && $this->activityAuthorization->canView($user, $workspace)) {
            $activityNotifications = [
                'recent' => WorkspaceActivityResource::collection(
                    $this->activities->recent($workspace, $user),
                )->resolve($request),
                'unreadCount' => $this->activities->unreadCount($workspace, $user),
            ];
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'workspace' => $workspace === null ? null : new WorkspaceResource($workspace),
            'auth' => [
                'user' => $user instanceof User ? new UserResource($user) : null,
            ],
            'activityNotifications' => $activityNotifications,
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
        ];
    }
}
