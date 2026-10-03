<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $role): Response
    {   

        $user = $request->user();

        if(! $user) {
            abort(401, 'unauthorised');
        }

       if (! $request->user()->hasRole($role)) {
            abort(403, 'invalid role');
       }

       if (! $request->user()->hasRole('super_admin')) {
            abort(403, 'donot have super_admin privileges');
       }


        return $next($request);
    }
}
