<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Content;
use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use App\Models\Assignment;
use App\Models\QuizSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GradingController extends Controller
{
    public function getOverview(Request $request)
    {
        try {
            $user = Auth::user();
            $courseUuids = Course::where('instructor_id', $user->uuid)->pluck('uuid')->toArray();

            if (empty($courseUuids)) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'totalSubmissions' => 0,
                        'pendingReview' => 0,
                        'averageScore' => 0,
                        'recentActivity' => []
                    ]
                ]);
            }

            // Assignment Metrics
            $assignmentIds = Assignment::whereIn('course_uuid', $courseUuids)->pluck('id')->toArray();
            
            $totalAssignmentsSubmissions = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)->count();
            $pendingAssignments = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
                ->where('status', 'pending')
                ->count();

            $gradedSubs = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
                ->where('status', 'graded')
                ->whereNotNull('grade')
                ->with('assignment')
                ->get();

            // Quiz Metrics
            $quizIds = QuizSetting::whereIn('course_id', $courseUuids)->pluck('id')->toArray();
            $totalQuizAttempts = QuizAttempt::whereIn('quiz_setting_id', $quizIds)->count();

            $allAttempts = QuizAttempt::whereIn('quiz_setting_id', $quizIds)->get();

            // Calculate overall global average percentage across all assessments
            $percentages = [];
            foreach ($gradedSubs as $s) {
                $max = $s->assignment ? $s->assignment->points : 100;
                if ($max > 0) {
                    $percentages[] = ($s->grade / $max) * 100;
                }
            }
            foreach ($allAttempts as $a) {
                if ($a->total_points > 0) {
                    $percentages[] = ($a->score / $a->total_points) * 100;
                }
            }

            $avg = count($percentages) > 0 ? round(array_sum($percentages) / count($percentages)) : 0;

            // Recent activity (last 10 submissions)
            $recentSubmissions = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
                ->with(['user', 'assignment'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function($item) {
                    $userName = $item->user ? $item->user->first_name . ' ' . $item->user->last_name : 'Unknown Student';
                    return [
                        'id' => $item->uuid,
                        'type' => 'assignment',
                        'student' => $userName,
                        'email' => $item->user ? $item->user->email : null,
                        'title' => $item->assignment ? $item->assignment->title : 'Assignment',
                        'time' => $item->created_at->diffForHumans(),
                        'status' => $item->status
                    ];
                });

            $recentQuizzes = QuizAttempt::whereIn('quiz_setting_id', $quizIds)
                ->with(['user', 'quizSetting'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function($item) {
                    $userName = $item->user ? $item->user->first_name . ' ' . $item->user->last_name : 'Unknown Student';
                    return [
                        'id' => $item->uuid,
                        'type' => 'quiz',
                        'student' => $userName,
                        'email' => $item->user ? $item->user->email : null,
                        'title' => $item->quizSetting ? $item->quizSetting->title : 'Quiz',
                        'time' => $item->created_at->diffForHumans(),
                        'status' => $item->status
                    ];
                });

            $recentActivity = $recentSubmissions->concat($recentQuizzes)->sortByDesc('time')->take(6)->values();

            return response()->json([
                'status' => true,
                'data' => [
                    'totalSubmissions' => $totalAssignmentsSubmissions + $totalQuizAttempts,
                    'pendingReview' => $pendingAssignments,
                    'averageScore' => $avg,
                    'recentActivity' => $recentActivity
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCourses(Request $request)
    {
        try {
            $user = Auth::user();
            $courses = Course::where('instructor_id', $user->uuid)
                ->withCount(['contents' => function($q) {
                    $q->whereIn('contentType', ['assignment', 'quiz']);
                }])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $courses
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getAssessments($courseUuid)
    {
        try {
            $user = Auth::user();
            // Ensure trainer owns the course
            $course = Course::where('uuid', $courseUuid)->where('instructor_id', $user->uuid)->firstOrFail();

            $contents = Content::where('course_id', $courseUuid)
                ->whereIn('contentType', ['assignment', 'quiz'])
                ->get()
                ->map(function($item) {
                    $totalSubs = 0;
                    $pendingSubs = 0;
                    
                    if ($item->contentType === 'assignment' && $item->contentable_id) {
                        $totalSubs = AssignmentSubmission::where('assignment_id', $item->contentable_id)->count();
                        $pendingSubs = AssignmentSubmission::where('assignment_id', $item->contentable_id)->where('status', 'pending')->count();
                    } elseif ($item->contentType === 'quiz' && $item->contentable_id) {
                        $totalSubs = QuizAttempt::where('quiz_setting_id', $item->contentable_id)->count();
                        // Quizzes usually don't have "pending" unless configured manually, for now treat as auto-graded
                        $pendingSubs = 0; 
                    }

                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'type' => $item->contentType,
                        'contentable_id' => $item->contentable_id,
                        'totalSubmissions' => $totalSubs,
                        'pendingReview' => $pendingSubs
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => [
                    'course' => $course,
                    'assessments' => $contents
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSubmissions($contentId)
    {
        try {
            $user = Auth::user();
            $content = Content::findOrFail($contentId);
            
            // Simple verification that trainer owns this content could be added via course table relation
            
            $results = [];
            
            if ($content->contentType === 'assignment') {
                $results = AssignmentSubmission::where('assignment_id', $content->contentable_id)
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($sub) {
                        return [
                            'id' => $sub->id,
                            'type' => 'assignment',
                            'userId' => $sub->user_id,
                            'userName' => $sub->user ? $sub->user->first_name . ' ' . $sub->user->last_name : 'Unknown User',
                            'userEmail' => $sub->user ? $sub->user->email : null,
                            'userAvatar' => $sub->user ? $sub->user->image : null,
                            'submittedAt' => $sub->created_at->toISOString(),
                            'status' => $sub->status,
                            'grade' => $sub->grade,
                            'file_path' => $sub->file_path,
                            'content_text' => $sub->content,
                            'feedback' => $sub->feedback
                        ];
                    });
            } elseif ($content->contentType === 'quiz') {
                $results = QuizAttempt::where('quiz_setting_id', $content->contentable_id)
                    ->with('user')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($att) {
                        return [
                            'id' => $att->id,
                            'type' => 'quiz',
                            'userId' => $att->user_id,
                            'userName' => $att->user ? $att->user->first_name . ' ' . $att->user->last_name : 'Unknown User',
                            'userEmail' => $att->user ? $att->user->email : null,
                            'submittedAt' => $att->created_at->toISOString(),
                            'status' => $att->status, // passed/failed
                            'grade' => $att->score,
                            'maxGrade' => $att->total_points,
                        ];
                    });
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'content' => $content,
                    'submissions' => $results
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function gradeSubmission(Request $request, $submissionId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'grade' => 'required|numeric|min:0',
                'feedback' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                 return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $sub = AssignmentSubmission::findOrFail($submissionId);
            $sub->update([
                'grade' => $request->grade,
                'feedback' => $request->feedback,
                'status' => 'graded'
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Submission graded successfully!',
                'data' => $sub
            ]);
        } catch (\Exception $e) {
             return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
