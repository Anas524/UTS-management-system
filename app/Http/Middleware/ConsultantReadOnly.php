<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConsultantReadOnly
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if ($u && $u->role === 'consultant') {
            if (!in_array($request->method(), ['GET','HEAD','OPTIONS'])) {
                abort(403, 'Read-only access for consultant accounts.');
            }
        }
        return $next($request);
    }
}
