<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Content;
use App\Models\ContentCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminTrainerController extends Controller
{
    /**
     * Get a listing of all trainers with course counts, total students, and virtual ratings.
     */
    public function index(Request $request)
    {
        try {
            $trainers = User::whereRaw('LOWER(user_type) LIKE ?', ['trainer%'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedTrainers = $trainers->map(function ($user) {
                // Find all courses created by this trainer
                $courseUuids = Course::where('user_id', $user->uuid)
                    ->orWhere('instructor_id', $user->uuid)
                    ->pluck('uuid');
                
                $coursesCount = $courseUuids->count();

                // Find total students enrolled in this trainer's courses
                $totalStudents = Enrollment::whereIn('course_id', $courseUuids)->count();

                // Compute a consistent virtual rating based on the trainer's UUID
                $ratingVal = 4.5 + (abs(crc32($user->uuid)) % 6) * 0.1;
                $rating = number_format($ratingVal, 1);

                return [
                    'id' => $user->uuid, // Using UUID as frontend ID for routes/actions
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'coursesCreated' => $coursesCount,
                    'totalStudents' => $totalStudents,
                    'rating' => $rating,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status ?: 'Active',
                    'specialization' => $user->bio,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Trainers fetched successfully',
                'data' => $formattedTrainers
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch trainers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created trainer.
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
                'specialization' => ['nullable', 'string', 'max:255'],
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
                'bio' => $request->specialization, // Map specialization to bio
                'user_type' => 'trainers',
            ]);

            // Compute a virtual rating based on the trainer's UUID
            $ratingVal = 4.5 + (abs(crc32($user->uuid)) % 6) * 0.1;
            $rating = number_format($ratingVal, 1);

            return response()->json([
                'status' => true,
                'message' => 'Trainer created successfully',
                'data' => [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'coursesCreated' => 0,
                    'totalStudents' => 0,
                    'rating' => $rating,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status,
                    'specialization' => $user->bio,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create trainer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified trainer.
     */
    public function update(Request $request, $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Trainer not found'
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
                'specialization' => ['nullable', 'string', 'max:255'],
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
                'bio' => $request->specialization ?: $user->bio, // Map specialization to bio
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            // Fetch current stats for response
            $courseUuids = Course::where('user_id', $user->uuid)
                ->orWhere('instructor_id', $user->uuid)
                ->pluck('uuid');
            
            $coursesCount = $courseUuids->count();
            $totalStudents = Enrollment::whereIn('course_id', $courseUuids)->count();
            $ratingVal = 4.5 + (abs(crc32($user->uuid)) % 6) * 0.1;
            $rating = number_format($ratingVal, 1);

            return response()->json([
                'status' => true,
                'message' => 'Trainer updated successfully',
                'data' => [
                    'id' => $user->uuid,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'coursesCreated' => $coursesCount,
                    'totalStudents' => $totalStudents,
                    'rating' => $rating,
                    'joinDate' => $user->created_at ? $user->created_at->format('Y-m-d') : null,
                    'status' => $user->status,
                    'specialization' => $user->bio,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update trainer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified trainer.
     */
    public function destroy($uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Trainer not found'
                ], 404);
            }

            DB::transaction(function () use ($user) {
                // Find all courses created by this trainer
                $courses = Course::where('user_id', $user->uuid)
                    ->orWhere('instructor_id', $user->uuid)
                    ->get();

                foreach ($courses as $course) {
                    // Delete enrollments and completions for this course
                    Enrollment::where('course_id', $course->uuid)->delete();
                    ContentCompletion::where('course_id', $course->uuid)->delete();
                    
                    // Delete quiz attempts, assignment submissions, survey responses for this course
                    DB::table('quiz_attempts')->where('course_id', $course->uuid)->delete();
                    DB::table('assignment_submissions')->where('course_id', $course->uuid)->delete();
                    DB::table('survey_responses')->where('course_id', $course->uuid)->delete();

                    // Delete sections and contents
                    $sections = Section::where('course_id', $course->uuid)->get();
                    foreach ($sections as $section) {
                        Content::where('section_id', $section->id)->delete();
                        $section->delete();
                    }

                    // Delete assignments, quizzes, surveys
                    DB::table('assignments')->where('course_id', $course->uuid)->delete();
                    DB::table('quiz_setting')->where('course_id', $course->uuid)->delete();
                    DB::table('survey')->where('course_id', $course->uuid)->delete();

                    $course->delete();
                }

                // Delete trainer
                $user->delete();
            });

            return response()->json([
                'status' => true,
                'message' => 'Trainer deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete trainer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
