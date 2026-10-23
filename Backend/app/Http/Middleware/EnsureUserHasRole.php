<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $this->forbidden('Anda belum terautentikasi.');
        }

        $role = $user->role?->value;

        if ($role === null || ! in_array($role, $roles, true)) {
            return $this->forbidden('Anda tidak memiliki akses ke fitur ini.');
        }

        return $next($request);
    }

    private function forbidden(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => 'FORBIDDEN',
        ], 403);
    }
}
