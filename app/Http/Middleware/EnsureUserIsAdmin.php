<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Admin || ! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($permission && ! $user->hasAdminPermission($permission)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
