<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;

class RegisteredUserController extends Controller
{
    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
                'phone' => ['required', 'string', 'max:50', 'unique:' . User::class],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create the user
            $user = User::create([
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            // Trigger the registered event
            event(new Registered($user));

            // Log the user in
            Auth::login($user);

            // Return the response
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user,
                'token' =>  $user->createToken('AuthToken')->plainTextToken, // Generate authentication token
            ], 201);
        } catch (\Exception $e) {
            // Handle any unexpected exceptions
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function store2(Request $request)
    {
      
      
        dd($request->all());
        try {
          
            // Validate the request
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:' . User::class],
               
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                
                return response()->json(['message' => $validator->errors()], 400);
        
            }

            // Create the user
            $user = User::create([
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'user_type' => 'learners'
               
            ]);

            // Trigger the registered event
            event(new Registered($user));

            // Log the user in
            // Auth::login($user);

            // Return the response
          

            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user,
                // 'token' =>  $user->createToken('AuthToken')->plainTextToken, // Generate authentication token
            ], 201);
        } catch (\Exception $e) {
            // Handle any unexpected exceptions
            
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update_password(Request $request) {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'uuid' => 'required',
                // 'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
               
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create the user
            $user = User::where('uuid',$request->uuid)->first();
            $user->password = Hash::make($request->password);
          
            // Log the user in
            Auth::login($user);

            // Return the response
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user,
                'token' =>  $user->createToken('AuthToken')->plainTextToken, // Generate authentication token
            ], 201);
        } catch (\Exception $e) {
            // Handle any unexpected exceptions
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function login(Request $request)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
            ]);

            // Check if validation fails
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check the user's credentials
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Generate authentication token
            $token = $user->createToken('AuthToken')->plainTextToken;

            // Return the response
            return response()->json([
                'status' => true,
                'message' => 'User logged in successfully',
                'data' => $user,
                'token' => $token,
            ], 200);
        } catch (\Exception $e) {
            // Handle any unexpected exceptions
            return response()->json([
                'message' => 'Failed to log in user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
