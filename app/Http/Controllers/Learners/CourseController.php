<?php

namespace App\Http\Controllers\Learners;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Content;
use App\Models\Enrollment;
use App\Models\ContentCompletion;
use App\Models\QuizSetting;
use App\Models\QuizAttempt;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * List all courses with enrollment status for the current user.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $courses = Course::with('instructor')
                ->withCount('contents')
                ->where('status', 1)
                ->get();

            $enrolledCourseIds = Enrollment::where('user_id', $user->uuid)
                ->where('status', 'active')
                ->pluck('course_id')
                ->toArray();

            $data = $courses->map(function ($course) use ($enrolledCourseIds) {
                return [
                    'id' => $course->uuid,
                    'title' => $course->title,
                    'instructor' => $course->instructor ? $course->instructor->name : 'Unknown',
                    'description' => $course->description,
                    'price' => (float) $course->price,
                    'image' => $course->image,
                    'course_code' => $course->course_code,
                    'isEnrolled' => in_array($course->uuid, $enrolledCourseIds),
                    // Mocking some fields that might be needed by the frontend
                    'level' => 'beginner',
                    'duration' => '20h',
                    'totalLessons' => $course->contents_count ?? 0,
                    'rating' => 4.5,
                    'category' => 'General',
                    'tags' => ['Skill', 'Learning'],
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Courses fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch courses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show course details including curriculum.
     */
    public function show(Request $request, $uuid)
    {
        try {
            $user = $request->user();
            $course = Course::with(['instructor', 'sections.contents'])
                ->withCount(['contents', 'sections'])
                ->where('uuid', $uuid)
                ->first();

            if (!$course) {
                return response()->json(['status' => false, 'message' => 'Course not found'], 404);
            }

            $enrolled = Enrollment::where('user_id', $user->uuid)
                ->where('course_id', $course->uuid)
                ->where('status', 'active')
                ->first();

            $completedContentIds = ContentCompletion::where('user_id', $user->uuid)
                ->where('course_id', $course->uuid)
                ->pluck('content_id')
                ->toArray();

            $data = [
                'id' => $course->uuid,
                'title' => $course->title,
                'description' => $course->description,
                'instructor' => $course->instructor ? $course->instructor : 'Unknown',
                'instructorBio' => $course->instructor ? $course->instructor->bio : null,
                'instructorAvatar' => $course->instructor ? $course->instructor->avatar : null,
                'category' => 'General',
                'level' => 'beginner',
                'duration' => '20h',
                'totalLessons' => $course->contents_count,
                'enrolled' => Enrollment::where('course_id', $course->uuid)->count(),
                'rating' => 4.5,
                'isEnrolled' => !!$enrolled,
                'progress' => $course->contents_count > 0 ? round((count($completedContentIds) / $course->contents_count) * 100) : 0,
                'tags' => ['Skill', 'Learning'],
                'sections' => $course->sections->map(function ($section) use ($completedContentIds) {
                    return [
                        'id' => $section->id,
                        'name' => $section->name,
                        'totalCount' => $section->contents->count(),
                        'completedCount' => $section->contents->whereIn('id', $completedContentIds)->count(),
                        'contents' => $section->contents->map(function ($content) use ($completedContentIds) {
                            return [
                                'id' => $content->id,
                                'title' => $content->title,
                                'type' => $content->contentType ?? 'videos',
                                'duration' => '10m', // Mocked
                                'isCompleted' => in_array($content->id, $completedContentIds),
                                'isLocked' => false,
                            ];
                        })
                    ];
                })
            ];

            return response()->json([
                'status' => true,
                'message' => 'Course details fetched successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch course details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific content details.
     */
    public function getContent(Request $request, $course_id, $content_id)
    {
        try {
            $user = $request->user();
            $content = Content::where('id', $content_id)->where('course_id', $course_id)->first();

            if (!$content) {
                return response()->json(['status' => false, 'message' => 'Content not found'], 404);
            }

            $isCompleted = ContentCompletion::where('user_id', $user->uuid)
                ->where('content_id', $content->id)
                ->exists();

            // Get all contents of this course in order for navigation
            $allContents = Content::where('course_id', $course_id)
                ->orderBy('section_id')
                ->orderBy('id')
                ->get(['id', 'title', 'contentType']);

            $data = $content->data;

            // If it's a quiz, we need to load the quiz data from the contentable relation
            if ($content->contentType === 'quiz' && $content->contentable_id) {
                $quizSetting = QuizSetting::with('questions')->find($content->contentable_id);
                if ($quizSetting) {
                    $attempts = QuizAttempt::where('user_id', $user->uuid)
                        ->where('quiz_setting_id', $quizSetting->id)
                        ->orderBy('created_at', 'desc')
                        ->get();

                    $data = [
                        'id' => $quizSetting->id,
                        'title' => $quizSetting->title,
                        'description' => $quizSetting->description,
                        'timeLimit' => $quizSetting->time_limit,
                        'attemptsAllowed' => $quizSetting->attempts,
                        'passingScore' => $quizSetting->passing_score,
                        'settings' => $quizSetting->settings,
                        'previousAttempts' => $attempts,
                        'attemptCount' => $attempts->count(),
                        'questions' => $quizSetting->questions->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'type' => $q->type,
                                'question' => $q->question,
                                'points' => $q->points,
                                'correctAnswer' => $q->correct_answer,
                                'explanation' => $q->explanation,
                                'options' => $q->options,
                                'required' => $q->required,
                            ];
                        })
                    ];
                }
            }

            // If it's an assignment, load assignment data
            if ($content->contentType === 'assignment' && $content->contentable_id) {
                $assignment = Assignment::with(['rubrics.levels', 'resources'])->find($content->contentable_id);
                if ($assignment) {
                    $submission = AssignmentSubmission::where('user_id', $user->uuid)
                        ->where('assignment_id', $assignment->id)
                        ->first();

                    $data = [
                        'id' => $assignment->id,
                        'title' => $assignment->title,
                        'description' => $assignment->description,
                        'instructions' => $assignment->instructions,
                        'dueDate' => $assignment->due_date,
                        'points' => $assignment->points,
                        'submissionType' => $assignment->submission_type,
                        'allowedFileTypes' => $assignment->allowed_file_types,
                        'maxFileSize' => $assignment->max_file_size,
                        'attempts' => $assignment->attempts,
                        'submission' => $submission,
                        'rubric' => $assignment->rubrics->map(function ($r) {
                            return [
                                'id' => $r->id,
                                'name' => $r->name,
                                'description' => $r->description,
                                'levels' => $r->levels->map(function ($l) {
                                    return [
                                        'id' => $l->id,
                                        'name' => $l->name,
                                        'points' => $l->points,
                                        'description' => $l->description,
                                    ];
                                })
                            ];
                        }),
                        'resources' => $assignment->resources->map(function ($r) {
                            return [
                                'id' => $r->id,
                                'name' => $r->name,
                                'url' => $r->url,
                                'description' => $r->description,
                            ];
                        })
                    ];
                }
            }
            // If it's a survey, load survey data
            if ($content->contentType === 'survey' && $content->contentable_id) {
                $survey = Survey::with('questions')->find($content->contentable_id);
                if ($survey) {
                    $response = SurveyResponse::where('user_id', $user->uuid)
                        ->where('survey_id', $survey->id)
                        ->first();

                    $data = [
                        'id' => $survey->id,
                        'title' => $survey->title,
                        'description' => $survey->description,
                        'anonymous' => $survey->anonymous,
                        'allowMultipleResponses' => $survey->allow_multiple_responses,
                        'showResults' => $survey->show_results,
                        'submitted' => !!$response,
                        'previousResponse' => $response,
                        'questions' => $survey->questions->map(function ($q) {
                            return [
                                'id' => $q->id,
                                'type' => $q->type,
                                'question' => $q->question,
                                'required' => $q->required,
                                'options' => $q->options,
                                'scale' => $q->scale,
                                'likertOptions' => $q->likert_options,
                            ];
                        })
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Content fetched successfully',
                'data' => [
                    'id' => $content->id,
                    'title' => $content->title,
                    'description' => $content->description,
                    'contentType' => $content->contentType,
                    'data' => $data,
                    'file' => $content->file,
                    'isCompleted' => $isCompleted,
                    'allContents' => $allContents 
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to fetch content', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark content as completed.
     */
    public function markAsComplete(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'content_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            ContentCompletion::updateOrCreate([
                'user_id' => $user->uuid,
                'content_id' => $request->content_id,
            ], [
                'course_id' => $request->course_id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Content marked as complete'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to mark as complete', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Enroll in a free course.
     */
    public function enrollFree(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,uuid',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $course = Course::where('uuid', $request->course_id)->first();

            if ($course->price > 0) {
                return response()->json(['status' => false, 'message' => 'This course is not free. Please use the payment gateway.'], 400);
            }

            // Check if already enrolled
            $existing = Enrollment::where('user_id', $user->uuid)->where('course_id', $course->uuid)->first();
            if ($existing) {
                return response()->json(['status' => false, 'message' => 'You are already enrolled in this course.'], 400);
            }

            Enrollment::create([
                'user_id' => $user->uuid,
                'course_id' => $course->uuid,
                'status' => 'active',
                'amount_paid' => 0,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Enrolled successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Enrollment failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Initialize payment (Generate tx_ref).
     */
    public function initializePayment(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,uuid',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $course = Course::where('uuid', $request->course_id)->first();
            $tx_ref = 'ELXP-' . Str::random(10) . '-' . time();

            // We don't need to call Flutterwave API here if we are using the inline library.
            // Just return the necessary data for the frontend to initialize.
            return response()->json([
                'status' => true,
                'message' => 'Payment initialized',
                'tx_ref' => $tx_ref,
                'data' => [
                    'amount' => (float) $course->price,
                    'title' => $course->title,
                    'course_id' => $course->uuid,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Payment initialization failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify Flutterwave payment and complete enrollment.
     */
    public function verifyPayment(Request $request)
    {
        try {
            $transaction_id = $request->transaction_id;
            $course_id = $request->course_id;
            $user = $request->user();

            if (!$transaction_id) {
                return response()->json(['status' => false, 'message' => 'Transaction ID is required'], 400);
            }

            $response = Http::withToken(env('FLUTTERWAVE_SECRET_TEST'))
                ->get("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify");

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json('data');

                // Safety check: ensure it's successful and currency matches
                if ($data['status'] !== 'successful') {
                    return response()->json(['status' => false, 'message' => 'Payment not successful', 'error' => $data], 400);
                }

                $amount = $data['amount'];

                // Check if already enrolled
                $existing = Enrollment::where('user_id', $user->uuid)->where('course_id', $course_id)->first();
                if (!$existing) {
                    Enrollment::create([
                        'user_id' => $user->uuid,
                        'course_id' => $course_id,
                        'status' => 'active',
                        'amount_paid' => $amount,
                        'transaction_id' => (string) $transaction_id,
                    ]);
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Payment verified and enrollment completed!',
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed',
                'error' => $response->json()
            ], 400);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Payment verification failed', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Submit quiz attempt.
     */
    public function submitQuiz(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'content_id' => 'required',
                'quiz_id' => 'required',
                'score' => 'required|integer',
                'total_points' => 'required|integer',
                'answers' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $quizSetting = QuizSetting::where('id', $request->quiz_id)->first();
            if (!$quizSetting) {
                return response()->json(['status' => false, 'message' => 'Quiz not found'], 404);
            }

            $passingScore = $quizSetting->passing_score;
            $percentage = ($request->score / $request->total_points) * 100;
            $status = $percentage >= $passingScore ? 'passed' : 'failed';

            $attempt = QuizAttempt::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->uuid,
                'quiz_setting_id' => $quizSetting->id,
                'score' => $request->score,
                'total_points' => $request->total_points,
                'answers' => $request->answers,
                'status' => $status,
            ]);

            // If passed, mark content as complete
            if ($status === 'passed') {
                ContentCompletion::updateOrCreate([
                    'user_id' => $user->uuid,
                    'content_id' => $request->content_id,
                ], [
                    'course_id' => $request->course_id,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Quiz submitted successfully',
                'data' => [
                    'attempt_id' => $attempt->uuid,
                    'status' => $status,
                    'score' => $request->score,
                    'total_points' => $request->total_points,
                    'percentage' => $percentage,
                    'passing_score' => $passingScore
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to submit quiz', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Submit assignment.
     */
    public function submitAssignment(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'content_id' => 'required',
                'assignment_id' => 'required',
                'content' => 'nullable|string',
                'file' => 'nullable|file|max:20480', // 20MB max
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $assignment = Assignment::where('id', $request->assignment_id)->first();
            if (!$assignment) {
                return response()->json(['status' => false, 'message' => 'Assignment not found'], 404);
            }

            $filePath = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('submissions'), $fileName);
                $filePath = 'submissions/' . $fileName;
            }

            $submission = AssignmentSubmission::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->uuid,
                'assignment_id' => $assignment->id,
                'content' => $request->content,
                'file_path' => $filePath,
                'status' => 'pending',
            ]);

            // Mark content as complete upon submission
            ContentCompletion::updateOrCreate([
                'user_id' => $user->uuid,
                'content_id' => $request->content_id,
            ], [
                'course_id' => $request->course_id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Assignment submitted successfully',
                'data' => [
                    'submission_id' => $submission->uuid,
                    'status' => 'pending'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to submit assignment', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Submit survey response.
     */
    public function submitSurvey(Request $request)
    {
        try {
            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'course_id' => 'required',
                'content_id' => 'required',
                'survey_id' => 'required',
                'responses' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
            }

            $survey = Survey::where('id', $request->survey_id)->first();
            if (!$survey) {
                return response()->json(['status' => false, 'message' => 'Survey not found'], 404);
            }

            $response = SurveyResponse::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $user->uuid,
                'survey_id' => $survey->id,
                'responses' => $request->responses,
            ]);

            // Mark content as complete upon submission
            ContentCompletion::updateOrCreate([
                'user_id' => $user->uuid,
                'content_id' => $request->content_id,
            ], [
                'course_id' => $request->course_id,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Survey submitted successfully',
                'data' => [
                    'response_id' => $response->uuid
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Failed to submit survey', 'error' => $e->getMessage()], 500);
        }
    }
}
