<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWorkspaceType
{
    public function handle(Request $request, Closure $next, string ...$allowedTypes): Response
    {
        $workspace = Workspace::current();

        abort_unless(
            $workspace instanceof Workspace
                && in_array($workspace->type->value, $allowedTypes, true),
            Response::HTTP_FORBIDDEN,
            'This page is not available for the current workspace type.',
        );

        return $next($request);
    }
}
