<?php

namespace App\Http\Controllers\Learners;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    /**
     * Handle learner registration.
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => 'learners'
            ]);

            event(new Registered($user));

            // Optional: Send Welcome Mail
            try {
                $email = $user->email;
                $data = array('name' => $user->first_name, 'uuid' => $user->uuid, 'email' => $email);
                Mail::send('mail.welcome', $data, function ($message) use ($email) {
                    $message->to($email)->subject('Welcome to CS-ELXP Learners');
                    $message->from('support@cttaste.com', 'CS-ELXP');
                });
                $mail_message = 'Welcome Mail Sent Successfully!';
            } catch (\Exception $e) {
                $mail_message = $e->getMessage();
            }

            $token = $user->createToken('AuthToken')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Learner registered successfully',
                'mail_status' => $mail_message,
                'data' => $user,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to register learner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle learner login.
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)
                        ->where('user_type', 'learners')
                        ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials or user is not a learner'
                ], 401);
            }

            $token = $user->createToken('AuthToken')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Learner logged in successfully',
                'data' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to log in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get authenticated learner profile.
     */
    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $request->user(),
        ]);
    }

    /**
     * Logout learner.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
