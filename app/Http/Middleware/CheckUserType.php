<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$allowedTypes  Allowed user types (e.g., 'administrator', 'site_manager')
     */
    public function handle(Request $request, Closure $next, string ...$allowedTypes): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $userTypeValue = $user->user_type->value;

        if (!in_array($userTypeValue, $allowedTypes)) {
            return response()->json([
                'message' => 'Unauthorized. Required user type: ' . implode(' or ', $allowedTypes),
                'your_type' => $userTypeValue
            ], 403);
        }

        return $next($request);
    }
}
