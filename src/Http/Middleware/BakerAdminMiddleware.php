<?php

namespace Flamerecca\Bakerflow\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BakerAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $allowedIps = json_decode(env('BAKER_ALLOWED_IPS'), true) ?? [];
        $ip = $request->ip();

        if (!in_array($ip, $allowedIps)) {
            return redirect("home");
        }

        return $next($request);
    }
}