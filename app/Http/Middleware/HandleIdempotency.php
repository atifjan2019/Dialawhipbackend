<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class HandleIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return $next($request);
        }

        $cacheKey = "idempotency:$key";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return response($cached['body'], $cached['status'])
                ->header('Content-Type', 'application/json')
                ->header('Idempotent-Replay', 'true');
        }

        $existing = IdempotencyKey::find($key);
        if ($existing) {
            return response()->json(['message' => 'Idempotent replay without cached body'], $existing->response_status)
                ->header('Idempotent-Replay', 'true');
        }

        $response = $next($request);

        if ($response->isSuccessful() || $response->getStatusCode() === 422) {
            $body = (string) $response->getContent();
            Cache::put($cacheKey, ['body' => $body, 'status' => $response->getStatusCode()], now()->addHours(24));
            IdempotencyKey::create([
                'key' => $key,
                'user_id' => $request->user()?->id,
                'response_hash' => hash('sha256', $body),
                'response_status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
