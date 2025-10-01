<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Area WMS
        if ($request->is('wms') || $request->is('wms/*')) {
            return route('wms.login');   // <-- pastikan route ini ada
        }

        // Area OMS (kalau belum punya rute bernama oms.login, pakai '/oms')
        if ($request->is('oms') || $request->is('oms/*')) {
            return url('/oms');          // atau: return route('oms.login');
        }

        // Default fallback
        return route('wms.login');
    }
}
