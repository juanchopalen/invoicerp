<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class EnsureFiscalRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlation = $request->header('X-Correlation-Id');
        $request->attributes->set('correlation_id', $correlation !== null && $correlation !== '' ? $correlation : (string) Str::uuid());
        $requestId = $request->header('X-Request-Id');
        $request->attributes->set('request_id', $requestId !== null && $requestId !== '' ? $requestId : null);

        return $next($request);
    }
}
