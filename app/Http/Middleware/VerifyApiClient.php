<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Client;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-Name');
        $clientSecret = $request->header('X-Client-Secret');
        $requestDomain = $request->getHost(); // ✅ Get domain from request URL

        if (!$clientId || !$clientSecret) {
            return response()->json(['error' => 'Unauthorized: Missing headers'], 401);
        }

        $client = Client::where('name', $clientId)->first();

        if (!$client) {
            return response()->json(['error' => 'Unauthorized: Client not found'], 401);
        }

        if ($client->domain && $client->domain !== $requestDomain) {
            return response()->json([
                'error'          => 'Unauthorized: Invalid domain',
            ], 401);
        }

    if (!password_verify($clientSecret, $client->secret)) {
        return response()->json(['error' => 'Unauthorized: Invalid client credentials'], 401);
    }


        return $next($request);
    }
}
