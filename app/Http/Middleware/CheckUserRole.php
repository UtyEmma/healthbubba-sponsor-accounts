<?php

namespace App\Http\Middleware;

use App\Enums\Account\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $role = 'user'): Response
    {
        $user = $request->user();
        
        if($role == 'user') {
            if(in_array($user->role, [Roles::ADMIN, Roles::SUPER_ADMIN])) {
                return redirect(route('filament.admin.auth.login'));
            }
        }
        
        return $next($request);
    }
}
