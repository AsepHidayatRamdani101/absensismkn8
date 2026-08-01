<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->user() || !$this->shouldLog($request)) {
            return $response;
        }

        $user = $request->user();
        $role = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'unknown') : 'unknown';
        $newValue = $request->except(['password', 'password_confirmation', 'current_password', 'token']);

        Log::channel('audit')->info('audit.request', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'role' => $role,
            'ip' => (string) $request->ip(),
            'browser' => (string) $request->userAgent(),
            'platform' => (string) $request->header('sec-ch-ua-platform', 'unknown'),
            'url' => (string) $request->fullUrl(),
            'method' => (string) $request->method(),
            'old_value' => null,
            'new_value' => $newValue,
            'response_status' => $response->getStatusCode(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return $response;
    }

    private function shouldLog(Request $request): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
