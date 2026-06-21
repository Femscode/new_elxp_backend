<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminManagementController extends Controller
{
    /**
     * Get a listing of all administrators.
     */
    public function index(Request $request)
    {
        try {
            $admins = User::whereRaw('LOWER(user_type) = ?', ['admin'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedAdmins = $admins->map(function ($user) {
                return [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status ?: 'Active',
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Admins fetched successfully',
                'data' => $formattedAdmins
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch admins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created administrator.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['nullable', 'string', 'max:255'],
                'first_name' => ['required_without:name', 'nullable', 'string', 'max:255'],
                'last_name' => ['required_without:name', 'nullable', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:6'],
                'phone' => ['nullable', 'string', 'max:50'],
                'status' => ['nullable', 'string', Rule::in(['Active', 'Inactive'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $firstName = $request->first_name;
            $lastName = $request->last_name;

            if ($request->filled('name') && (!$firstName || !$lastName)) {
                $parts = explode(' ', trim($request->name), 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }

            $uuid = (string) Str::uuid();

            $user = User::create([
                'uuid' => $uuid,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'status' => $request->status ?: 'Active',
                'user_type' => 'admin',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Admin created successfully',
                'data' => [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create admin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified administrator.
     */
    public function update(Request $request, $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => ['nullable', 'string', 'max:255'],
                'first_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:6'],
                'phone' => ['nullable', 'string', 'max:50'],
                'status' => ['nullable', 'string', Rule::in(['Active', 'Inactive'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $firstName = $request->first_name ?: $user->first_name;
            $lastName = $request->last_name ?: $user->last_name;

            if ($request->filled('name')) {
                $parts = explode(' ', trim($request->name), 2);
                $firstName = $parts[0] ?? '';
                $lastName = $parts[1] ?? '';
            }

            $updateData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $request->email,
                'phone' => $request->phone ?: $user->phone,
                'status' => $request->status ?: $user->status,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            return response()->json([
                'status' => true,
                'message' => 'Admin updated successfully',
                'data' => [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update admin',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified administrator.
     */
    public function destroy($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Admin not found'
                ], 404);
            }

            // Enforce constraint: We must always keep at least one admin
            $adminCount = User::whereRaw('LOWER(user_type) = ?', ['admin'])->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Action forbidden: At least one administrator account must remain on the platform.'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'status' => true,
                'message' => 'Admin deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete admin',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
