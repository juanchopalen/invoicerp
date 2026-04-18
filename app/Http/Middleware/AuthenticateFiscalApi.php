<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\User;
use App\Support\FiscalActor;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateFiscalApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if ($bearer === null || $bearer === '') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($bearer);
        if ($accessToken?->tokenable instanceof User) {
            $user = $accessToken->tokenable;
            if ($user->tenant_id === null) {
                return response()->json(['message' => 'User is not assigned to a tenant.'], 403);
            }
            TenantContext::set((int) $user->tenant_id);
            $request->attributes->set('fiscal_actor', FiscalActor::fromUser($user));

            return $next($request);
        }

        $client = ApiClient::findByPlainKey($bearer);
        if ($client !== null) {
            if ($client->status !== 'active') {
                return response()->json(['message' => 'API client is disabled.'], 403);
            }
            $client->touchLastUsed();
            TenantContext::set((int) $client->tenant_id);
            $request->attributes->set('fiscal_actor', FiscalActor::fromApiClient($client));

            return $next($request);
        }

        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    public function terminate(Request $request, Response $response): void
    {
        TenantContext::set(null);
    }
}
