<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireRole
{
    // pakai banyak role juga bisa: role:wms,admin
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        abort_unless($user, 401);

        // jika tidak ada param, fallback cek persis 'wms'
        if (empty($roles)) {
            $roles = ['wms'];
        }

        abort_unless(in_array($user->role, $roles, true), 403);
        return $next($request);
    }
}
