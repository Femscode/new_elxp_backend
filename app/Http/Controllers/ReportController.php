<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Content;
use App\Models\ContentCompletion;
use App\Models\Enrollment;
use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use App\Models\Assignment;
use App\Models\QuizSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function getMetrics(Request $request)
    {
        try {
            $user = Auth::user();
            $courseUuids = Course::where('instructor_id', $user->uuid)->pluck('uuid')->toArray();

            if (empty($courseUuids)) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'averageScore' => 0,
                        'completionRate' => 0,
                        'activeStudents' => 0,
                        'certificatesIssued' => 0,
                    ]
                ]);
            }

            // Active students
            $activeStudents = Enrollment::whereIn('course_id', $courseUuids)->distinct('user_id')->count('user_id');

            // Average Score (Assignments + Quizzes)
            $assignmentIds = Assignment::whereIn('course_uuid', $courseUuids)->pluck('id')->toArray();
            $gradedSubs = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
                ->where('status', 'graded')
                ->whereNotNull('grade')
                ->with('assignment')
                ->get();

            $quizIds = QuizSetting::whereIn('course_id', $courseUuids)->pluck('id')->toArray();
            $allAttempts = QuizAttempt::whereIn('quiz_setting_id', $quizIds)->get();

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
            $avg = count($percentages) > 0 ? round(array_sum($percentages) / count($percentages), 1) : 0;

            // Completion Rate and Certificates (rough estimation for now)
            $totalEnrollments = Enrollment::whereIn('course_id', $courseUuids)->count();
            $totalCompletions = 0;
            $totalContentCount = Content::whereIn('course_id', $courseUuids)->count();
            $totalCompletedContents = ContentCompletion::whereIn('course_id', $courseUuids)->count();
            
            $completionRate = ($totalEnrollments > 0 && $totalContentCount > 0) ? round(($totalCompletedContents / ($totalEnrollments * $totalContentCount)) * 100, 1) : 0;
            if ($completionRate > 100) $completionRate = 100;
            
            // Assume users who reached high completion got certificates
            $certificatesIssued = floor($totalEnrollments * ($completionRate / 100));

            return response()->json([
                'status' => true,
                'data' => [
                    'averageScore' => $avg,
                    'completionRate' => $completionRate,
                    'activeStudents' => $activeStudents,
                    'certificatesIssued' => $certificatesIssued,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getCoursePerformance(Request $request)
    {
        try {
            $user = Auth::user();
            $courses = Course::where('instructor_id', $user->uuid)->get();

            $performanceData = [];
            foreach ($courses as $course) {
                $enrollmentCount = Enrollment::where('course_id', $course->uuid)->count();
                $performanceData[] = [
                    'course' => $course->title,
                    'students' => $enrollmentCount
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $performanceData
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getDetailedReport(Request $request)
    {
        try {
            $user = Auth::user();
            $courseUuids = Course::where('instructor_id', $user->uuid)->pluck('uuid')->toArray();

            $enrollments = Enrollment::whereIn('course_id', $courseUuids)
                ->with(['user', 'course'])
                ->get();

            $detailedReport = [];
            foreach ($enrollments as $enrollment) {
                $courseContentCount = Content::where('course_id', $enrollment->course_id)->count();
                $userCompletedCount = ContentCompletion::where('course_id', $enrollment->course_id)
                    ->where('user_id', $enrollment->user_id)
                    ->count();

                $progress = $courseContentCount > 0 ? round(($userCompletedCount / $courseContentCount) * 100) : 0;

                // Grade calculation (Assignments + Quizzes for this user in this course)
                $assignmentIds = Assignment::where('course_uuid', $enrollment->course_id)->pluck('id')->toArray();
                $gradedSubs = AssignmentSubmission::whereIn('assignment_id', $assignmentIds)
                    ->where('user_id', $enrollment->user_id)
                    ->where('status', 'graded')
                    ->whereNotNull('grade')
                    ->with('assignment')
                    ->get();

                $quizIds = QuizSetting::where('course_id', $enrollment->course_id)->pluck('id')->toArray();
                $allAttempts = QuizAttempt::whereIn('quiz_setting_id', $quizIds)
                    ->where('user_id', $enrollment->user_id)
                    ->get();

                $percentages = [];
                foreach ($gradedSubs as $s) {
                    $max = $s->assignment ? $s->assignment->points : 100;
                    if ($max > 0) $percentages[] = ($s->grade / $max) * 100;
                }
                foreach ($allAttempts as $a) {
                    if ($a->total_points > 0) $percentages[] = ($a->score / $a->total_points) * 100;
                }
                $avg = count($percentages) > 0 ? round(array_sum($percentages) / count($percentages)) : 0;

                // Grade formatting
                $grade = 'N/A';
                $gradeColor = 'blue';
                if ($avg >= 90) { $grade = 'A'; $gradeColor = 'green'; }
                else if ($avg >= 80) { $grade = 'B'; $gradeColor = 'blue'; }
                else if ($avg >= 70) { $grade = 'C'; $gradeColor = 'yellow'; }
                else if ($avg > 0) { $grade = 'D'; $gradeColor = 'red'; }

                $detailedReport[] = [
                    'id' => $enrollment->id,
                    'name' => $enrollment->user ? $enrollment->user->first_name . ' ' . $enrollment->user->last_name : 'Unknown',
                    'email' => $enrollment->user ? $enrollment->user->email : 'N/A',
                    'initials' => $enrollment->user ? substr($enrollment->user->first_name, 0, 1) . substr($enrollment->user->last_name, 0, 1) : 'U',
                    'color' => '#1849a9',
                    'course' => $enrollment->course ? $enrollment->course->title : 'Unknown Course',
                    'progress' => $progress,
                    'lastLogin' => $enrollment->user && $enrollment->user->updated_at ? $enrollment->user->updated_at->diffForHumans() : 'N/A',
                    'grade' => $grade,
                    'gradeColor' => $gradeColor,
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $detailedReport
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
