<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);

        /*
         * Important:
         * recharge les valeurs par défaut créées par la base,
         * notamment:
         *
         * role   = customer
         * status = active
         */
        $user->refresh();

        Auth::guard('web')->login($user);

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $credentials = [
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if (! Auth::guard('web')->attempt(
            $credentials,
            (bool) ($data['remember'] ?? false)
        )) {
            throw ValidationException::withMessages([
                'email' => [
                    'The provided credentials are incorrect.',
                ],
            ]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (! $user->isActive()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'message' => 'Account is inactive.',
            ], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Authenticated successfully.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
