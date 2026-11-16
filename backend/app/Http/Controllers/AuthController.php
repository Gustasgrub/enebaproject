<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Token authentication with Sanctum. The frontend and the API are on different sites, so a cookie
 * session would depend on third-party cookies; instead each login returns a bearer token
 * (docs/DECISIONS.md #24).
 */
class AuthController extends Controller
{
    /** POST /api/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        // The model's `hashed` cast hashes the password before it is stored.
        $user = User::create($request->validated());

        return $this->sessionResponse($user, 201);
    }

    /** POST /api/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        // One message for an unknown email and a wrong password, so the form does not reveal which
        // email addresses have accounts.
        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        return $this->sessionResponse($user, 200);
    }

    /** POST /api/logout: revokes only the token of this browser; other devices stay logged in. */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /** GET /api/user: who the token belongs to. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userData($request->user())]);
    }

    private function sessionResponse(User $user, int $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $user->createToken('web')->plainTextToken,
                'user' => $this->userData($user),
            ],
        ], $status);
    }

    /** @return array{id: int, name: string, email: string, is_admin: bool} */
    private function userData(User $user): array
    {
        // is_admin only decides whether the frontend shows the admin link; the API checks it again itself.
        return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'is_admin' => $user->isAdmin()];
    }
}
