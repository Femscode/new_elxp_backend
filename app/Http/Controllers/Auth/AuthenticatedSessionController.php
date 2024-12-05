<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TransactionController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */


    public function store(LoginRequest $request)
    {
        try {
            $request->authenticate(); // Authenticate user

            $user = $request->user(); // Get authenticated user

            $token = $user->createToken('AuthToken')->plainTextToken; // Generate authentication token

            return response()->json([
                'status' => true,
                'data' => $user, // Return user details
                'token' => $token, // Return authentication token
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 401); // Handle validation errors
        } 
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {

        Auth::guard('web')->logout();

        return response()->json([
            "data" => "User logged out successfully!",
            "status" => true
        ]);


        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
