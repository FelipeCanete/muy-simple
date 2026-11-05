<?php

namespace App\Http\Controllers;

use App\Exceptions\Domain\InvalidCredentials;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Autenticación del panel con sesión de Sanctum (cookie HttpOnly).
 * El navegador nunca maneja tokens: nada que robar con XSS en localStorage.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::guard('web')->attempt($request->only('email', 'password'))) {
            Log::warning('auth.login_fallido', ['ip' => $request->ip()]);
            throw new InvalidCredentials;
        }

        // Evita fijación de sesión (OWASP A07).
        $request->session()->regenerate();

        return response()->json(['data' => $this->userPayload($request)]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($request)]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    private function userPayload(Request $request): array
    {
        $user = $request->user();

        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
    }
}
