<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CartStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginCustomerRequest;
use App\Http\Requests\Auth\RegisterCustomerRequest;
use App\Http\Requests\Auth\UpdateCustomerPasswordRequest;
use App\Http\Requests\Auth\UpdateCustomerProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function user(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user() ? UserResource::make($request->user()) : null,
        ]);
    }

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Customer,
            'is_active' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $this->attachGuestCart($user, $validated['cart_session_id'] ?? $request->header('X-Cart-Session'));

        return response()->json(['data' => UserResource::make($user)], 201);
    }

    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $loginColumn = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (! Auth::attempt([$loginColumn => $validated['login'], 'password' => $validated['password'], 'is_active' => true], $validated['remember'] ?? false)) {
            return response()->json(['message' => 'The provided credentials do not match our records.'], 422);
        }

        $request->session()->regenerate();
        $user = $request->user();
        $this->attachGuestCart($user, $validated['cart_session_id'] ?? $request->header('X-Cart-Session'));

        return response()->json(['data' => UserResource::make($user)]);
    }

    public function updateProfile(UpdateCustomerProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        return response()->json(['data' => UserResource::make($user->fresh())]);
    }

    public function updatePassword(UpdateCustomerPasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out.']);
    }

    private function attachGuestCart(User $user, ?string $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        $guestCart = Cart::query()
            ->where('session_id', $sessionId)
            ->where('status', CartStatus::Active)
            ->whereNull('user_id')
            ->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', CartStatus::Active)
            ->first();

        if (! $userCart) {
            $guestCart->update(['user_id' => $user->id]);

            return;
        }

        foreach ($guestCart->items as $item) {
            $existingItem = $userCart->items()
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $item->quantity);
                $item->delete();
            } else {
                $item->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->update(['status' => CartStatus::Abandoned]);
    }
}
