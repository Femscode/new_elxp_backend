<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Content;
use App\Models\ContentCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminLearnerController extends Controller
{
    /**
     * Get a listing of all learners with their courses count and completion rate.
     */
    public function index(Request $request)
    {
        try {
            $learners = User::whereRaw('LOWER(user_type) LIKE ?', ['learner%'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedLearners = $learners->map(function ($user) {
                // Fetch enrollments
                $enrollments = Enrollment::where('user_id', $user->uuid)->get();
                $enrolledCoursesCount = $enrollments->count();
                
                $totalProgress = 0;
                if ($enrolledCoursesCount > 0) {
                    foreach ($enrollments as $enrollment) {
                        $totalContents = Content::where('course_id', $enrollment->course_id)->count();
                        if ($totalContents > 0) {
                            $completions = ContentCompletion::where('course_id', $enrollment->course_id)
                                ->where('user_id', $user->uuid)
                                ->count();
                            
                            $totalProgress += min(($completions / $totalContents) * 100, 100);
                        }
                    }
                    $completionRate = round($totalProgress / $enrolledCoursesCount) . '%';
                } else {
                    $completionRate = '0%';
                }

                return [
                    'id' => $user->uuid, // Using UUID as frontend ID for routes/actions
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'enrolledCourses' => $enrolledCoursesCount,
                    'completionRate' => $completionRate,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status ?: 'Active',
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Learners fetched successfully',
                'data' => $formattedLearners
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch learners',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created learner.
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
                'user_type' => 'learners',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Learner created successfully',
                'data' => [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'enrolledCourses' => 0,
                    'completionRate' => '0%',
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create learner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified learner.
     */
    public function update(Request $request, $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Learner not found'
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

            // Fetch current enrollment stats for response
            $enrollments = Enrollment::where('user_id', $user->uuid)->get();
            $enrolledCoursesCount = $enrollments->count();
            
            $totalProgress = 0;
            if ($enrolledCoursesCount > 0) {
                foreach ($enrollments as $enrollment) {
                    $totalContents = Content::where('course_id', $enrollment->course_id)->count();
                    if ($totalContents > 0) {
                        $completions = ContentCompletion::where('course_id', $enrollment->course_id)
                            ->where('user_id', $user->uuid)
                            ->count();
                        
                        $totalProgress += min(($completions / $totalContents) * 100, 100);
                    }
                }
                $completionRate = round($totalProgress / $enrolledCoursesCount) . '%';
            } else {
                $completionRate = '0%';
            }

            return response()->json([
                'status' => true,
                'message' => 'Learner updated successfully',
                'data' => [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'enrolledCourses' => $enrolledCoursesCount,
                    'completionRate' => $completionRate,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update learner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified learner.
     */
    public function destroy($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Learner not found'
                ], 404);
            }

            DB::transaction(function () use ($user) {
                // Delete associated enrollments
                Enrollment::where('user_id', $user->uuid)->delete();
                
                // Delete content completions
                ContentCompletion::where('user_id', $user->uuid)->delete();
                
                // Delete other quiz attempts, assignment submissions, survey responses
                DB::table('quiz_attempts')->where('user_id', $user->uuid)->delete();
                DB::table('assignment_submissions')->where('user_id', $user->uuid)->delete();
                DB::table('survey_responses')->where('user_id', $user->uuid)->delete();

                // Delete learner
                $user->delete();
            });

            return response()->json([
                'status' => true,
                'message' => 'Learner deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete learner',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
