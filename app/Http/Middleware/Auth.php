<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as InnerAuth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class Auth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization');
        $response = Http::withHeaders([
            'Authorization' => $authorization,
            'Content-Type' => 'application/json',
        ])->get(env('AUTH_URL').'/api/auth/me');

        if ($response->successful()) {
            InnerAuth::login(User::find($response->json()['id']));
            return $next($request);
        }
        return response()->json(['error' => 'API request failed'], 400);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        // Your code to execute after the controller and response.
        // Example: Logging, post-processing, etc.
        if (InnerAuth::check()) {
            InnerAuth::logout();
        }
    }
}
