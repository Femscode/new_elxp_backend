<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Content;
use App\Models\ContentCompletion;
use App\Models\AssignmentSubmission;
use App\Models\Discussion;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Handle admin login request.
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

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            // Verify the user is actually an administrator
            if (strtolower($user->user_type) !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Access Denied: Only administrators can log in here.'
                ], 403);
            }

            // Generate Sanctum token
            $token = $user->createToken('AdminAuthToken')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Admin logged in successfully',
                'data' => $user,
                'token' => $token,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred during login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get platform overview stats for the admin.
     */
    public function overview(Request $request)
    {
        try {
            // 1. Total Learners
            $totalLearners = User::whereRaw('LOWER(user_type) LIKE ?', ['learner%'])->count();

            // 2. Active Trainers
            $activeTrainers = User::whereRaw('LOWER(user_type) LIKE ?', ['trainer%'])->count();

            // 3. Completion Rate and Certificates Issued
            $enrollments = Enrollment::all();
            $totalProgress = 0;
            $countedEnrollments = 0;
            $certificatesIssued = 0;

            foreach ($enrollments as $enrollment) {
                $totalContents = Content::where('course_id', $enrollment->course_id)->count();
                if ($totalContents > 0) {
                    $completions = ContentCompletion::where('course_id', $enrollment->course_id)
                        ->where('user_id', $enrollment->user_id)
                        ->count();
                    
                    $progress = ($completions / $totalContents) * 100;
                    $totalProgress += min($progress, 100);
                    $countedEnrollments++;

                    if ($completions >= $totalContents) {
                        $certificatesIssued++;
                    }
                }
            }

            $completionRate = $countedEnrollments > 0 ? round($totalProgress / $countedEnrollments, 1) : 0;

            // 4. Enrollment Trend (last 6 months)
            $trends = [];
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthName = $date->format('M');
                $monthNum = $date->format('m');
                $year = $date->format('Y');

                $count = Enrollment::whereMonth('created_at', $monthNum)
                    ->whereYear('created_at', $year)
                    ->count();
                
                $trends[] = [
                    'month' => $monthName,
                    'enrollments' => $count
                ];
            }

            // 5. Recent Activities
            $newUsers = User::orderBy('created_at', 'desc')->take(10)->get();
            $newEnrollments = Enrollment::with(['user', 'course'])->orderBy('created_at', 'desc')->take(10)->get();
            $newCompletions = ContentCompletion::with('content')->orderBy('created_at', 'desc')->take(10)->get();

            $activities = [];
            foreach ($newUsers as $u) {
                $activities[] = [
                    'id' => 'reg_' . $u->id,
                    'type' => 'registration',
                    'title' => 'New learner registered: ' . $u->first_name . ' ' . $u->last_name,
                    'time' => $u->created_at ? $u->created_at->diffForHumans() : 'Just now',
                    'timestamp' => $u->created_at ? $u->created_at->timestamp : time()
                ];
            }

            foreach ($newEnrollments as $e) {
                if ($e->user && $e->course) {
                    $activities[] = [
                        'id' => 'enroll_' . $e->id,
                        'type' => 'enrollment',
                        'title' => $e->user->first_name . ' ' . $e->user->last_name . ' enrolled in "' . $e->course->title . '"',
                        'time' => $e->created_at ? $e->created_at->diffForHumans() : 'Just now',
                        'timestamp' => $e->created_at ? $e->created_at->timestamp : time()
                    ];
                }
            }

            usort($activities, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            $activities = array_slice($activities, 0, 6);

            // 6. Quick Stats
            $pendingApprovals = AssignmentSubmission::where('status', 'submitted')->count();
            $activeCourses = Course::count();
            $completedThisMonth = ContentCompletion::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'totalLearners' => $totalLearners,
                    'activeTrainers' => $activeTrainers,
                    'certificatesIssued' => $certificatesIssued,
                    'completionRate' => $completionRate,
                    'enrollmentTrend' => $trends,
                    'recentActivities' => $activities,
                    'quickStats' => [
                        'pendingApprovals' => $pendingApprovals,
                        'activeCourses' => $activeCourses,
                        'completedThisMonth' => $completedThisMonth,
                        'totalDiscussions' => Discussion::count(),
                        'totalGroups' => Group::count(),
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to load dashboard overview data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
