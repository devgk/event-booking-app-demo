<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EventAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
		// Check if the Authorization header is present
		if (!$request->hasHeader('Authorization')) {
			return response()->json([
				'message' => 'Authorization token not found'
			], 401);
		}

		// Extract token from the Authorization header
		$token = $request->bearerToken();

		// If token is not provided
		if (empty($token)) {
			return response()->json([
				'message' => 'Authorization token is missing'
			], 401);
		}

		// Attempt to authenticate the user via the token
		if (Auth::guard('api')->check()) {
			// Get the authenticated user
			$user = Auth::guard('api')->user();

			// Check if the user's role_id is 1 (Admin)
			if ($user->role_id == 1) {
				// Proceed with the request if role is 1 (Admin)
				return $next($request);
			} else {
				// Return unauthorized response if role_id is not 1
				return response()->json([
					'message' => 'Unauthorized: User does not have the required privileges'
				], 403);
			}
		}

		// If authentication fails (invalid or expired token)
		return response()->json([
			'message' => 'Unauthorized: Invalid or expired token'
		], 401);
    }
}
