<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Models\QuizQuestions;
use App\Models\QuizSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class QuizController extends Controller
{
    public function create(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                // Quiz Setting fields
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'time_limit' => 'required|integer|min:1',
                'attempts' => 'required|integer|min:1',
                'passing_score' => 'required|integer|min:0',
                'course_id' => 'required|exists:courses,id',
                'content_id' => 'required|exists:course_contents,id',
                'status' => 'in:draft,published,archived',
                'settings' => 'required|array',
                'settings.shuffleQuestions' => 'required|boolean',
                'settings.shuffleOptions' => 'required|boolean',
                'settings.showResults' => 'required|boolean',
                'settings.immediateFeedback' => 'required|boolean',


                // Questions
                'questions' => 'required|array|min:1',
                'questions.*.type' => 'required|string|in:multiple_choice,true_false,short_answer',
                'questions.*.question' => 'required|string',
                'questions.*.points' => 'required|integer|min:1',
                'questions.*.correct_answer' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
                            $fail($attribute . ' must be a string, number, or boolean.');
                        }
                    },
                ],
                'questions.*.options' => 'nullable|array',
                'questions.*.explanation' => 'nullable|string',
                'questions.*.required' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Create quiz setting
            $quizSetting = QuizSetting::create([
                'user_id' => $user->id,
                'content_id' => $validated['content_id'],
                'uuid' => Str::uuid(),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'time_limit' => $validated['time_limit'],
                'attempts' => $validated['attempts'],
                'passing_score' => $validated['passing_score'],
                'settings' => $validated['settings'] ?? [],
                'course_id' => $validated['course_id'],
                'status' => $validated['status'] ?? 'draft',
            ]);

            $content = Content::find($validated['content_id']);
            if ($content) {
                $content->update([
                    'contentable_id' => $quizSetting->id,
                    'contentable_type' => QuizSetting::class,
                    'title' => $quizSetting->title,
                    'description' => $quizSetting->description,
                    'contentType' => 'quiz'
                ]);
            }
            // Create questions
            foreach ($validated['questions'] as $q) {
                QuizQuestions::create([
                    'uuid' => Str::uuid(),
                    'quiz_setting_id' => $quizSetting->id,
                    'type' => $q['type'],
                    'question' => $q['question'],
                    'points' => $q['points'],
                    'correct_answer' => $q['correct_answer'],
                    'options' => $q['options'] ?? [],
                    'explanation' => $q['explanation'] ?? null,
                    'required' => $q['required'] ?? false,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Quiz created successfully!',
                'data' => $quizSetting->load('questions')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $quizSetting = QuizSetting::where('content_id',$id)->first();

            if (!$quizSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quiz not found or access denied.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'time_limit' => 'sometimes|integer|min:1',
                'attempts' => 'sometimes|integer|min:1',
                'passing_score' => 'sometimes|integer|min:0',
                'course_id' => 'sometimes|exists:courses,id',
                'content_id' => 'sometimes|exists:course_contents,id',
                'status' => 'sometimes|in:draft,published,archived',
                'settings' => 'sometimes|array',
                'settings.shuffleQuestions' => 'sometimes|boolean',
                'settings.shuffleOptions' => 'sometimes|boolean',
                'settings.showResults' => 'sometimes|boolean',
                'settings.immediateFeedback' => 'sometimes|boolean',

                // Questions
                'questions' => 'sometimes|array|min:1',
                'questions.*.id' => 'sometimes|exists:quiz_questions,id',
                'questions.*.type' => 'required_with:questions|string|in:multiple_choice,true_false,short_answer,essay',
                'questions.*.question' => 'required_with:questions|string',
                'questions.*.points' => 'required_with:questions|integer|min:1',
                'questions.*.correct_answer' => [
                    'required_with:questions',
                    function ($attribute, $value, $fail) {
                        if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
                            $fail($attribute . ' must be a string, number, or boolean.');
                        }
                    },
                ],
                'questions.*.options' => 'sometimes|array',
                'questions.*.explanation' => 'nullable|string',
                'questions.*.required' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Update quiz setting
            $quizSetting->update(collect($validated)->except(['questions'])->toArray());

            // Handle Questions update
            if (isset($validated['questions'])) {
                $updatedQuestionIds = [];

                foreach ($validated['questions'] as $questionData) {
                    if (isset($questionData['id'])) {
                        // Update existing question
                        $question = QuizQuestions::where('id', $questionData['id'])
                            ->where('quiz_setting_id', $quizSetting->id)
                            ->first();
                        if ($question) {
                            $question->update([
                                'type' => $questionData['type'],
                                'question' => $questionData['question'],
                                'points' => $questionData['points'],
                                'correct_answer' => $questionData['correct_answer'],
                                'options' => $questionData['options'] ?? [],
                                'explanation' => $questionData['explanation'] ?? null,
                                'required' => $questionData['required'] ?? false,
                            ]);
                        }
                    } else {
                        // Create new question
                        $question = QuizQuestions::create([
                            'uuid' => Str::uuid(),
                            'quiz_setting_id' => $quizSetting->id,
                            'type' => $questionData['type'],
                            'question' => $questionData['question'],
                            'points' => $questionData['points'],
                            'correct_answer' => $questionData['correct_answer'],
                            'options' => $questionData['options'] ?? [],
                            'explanation' => $questionData['explanation'] ?? null,
                            'required' => $questionData['required'] ?? false,
                        ]);
                    }
                    $updatedQuestionIds[] = $question->id;
                }

                // Delete questions not in update
                QuizQuestions::where('quiz_setting_id', $quizSetting->id)
                    ->whereNotIn('id', $updatedQuestionIds)
                    ->delete();
            }

            return response()->json([
                'status' => true,
                'message' => 'Quiz updated successfully!',
                'data' => $quizSetting->fresh()->load('questions')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $user = Auth::user();
            $quizSetting = QuizSetting::with(['questions'])
                ->where('id', $id)
                ->first();

            if (!$quizSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quiz not found.'
                ], 404);
            }

            // Format response to match requirements
            $formattedQuiz = [
                'id' => $quizSetting->id,
                'title' => $quizSetting->title,
                'description' => $quizSetting->description,
                'timeLimit' => $quizSetting->time_limit,
                'attempts' => $quizSetting->attempts,
                'passingScore' => $quizSetting->passing_score,
                'settings' => $quizSetting->settings,
                'questions' => $quizSetting->questions->map(function ($question) {
                    return [
                        'id' => $question->uuid,
                        'type' => $question->type,
                        'question' => $question->question,
                        'points' => $question->points,
                        'correctAnswer' => $question->correct_answer,
                        'explanation' => $question->explanation,
                        'options' => $question->options,
                        'required' => $question->required,
                    ];
                }),
                'courseId' => $quizSetting->course_id,
                'createdAt' => $quizSetting->created_at->toISOString(),
                'updatedAt' => $quizSetting->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Quiz retrieved successfully!',
                'data' => $formattedQuiz
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    public function oldfetch($content_id)
    {
        try {
            $user = Auth::user();

            $quizSettings = QuizSetting::with(['questions'])
                ->where('content_id', $content_id)
                ->get();

            if ($quizSettings->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No quizzes found for this content.'
                ], 404);
            }

            // Format response for all quizzes
            $formattedQuizzes = $quizSettings->map(function ($quizSetting) {
                return [
                    'id' => $quizSetting->uuid,
                    'title' => $quizSetting->title,
                    'description' => $quizSetting->description,
                    'timeLimit' => $quizSetting->time_limit,
                    'attempts' => $quizSetting->attempts,
                    'passingScore' => $quizSetting->passing_score,
                    'settings' => $quizSetting->settings,
                    'questions' => $quizSetting->questions->map(function ($question) {
                        return [
                            'id' => $question->uuid,
                            'type' => $question->type,
                            'question' => $question->question,
                            'points' => $question->points,
                            'correctAnswer' => $question->correct_answer,
                            'explanation' => $question->explanation,
                            'options' => $question->options,
                            'required' => $question->required,
                        ];
                    }),
                    'courseId' => $quizSetting->course_id,
                    'createdAt' => $quizSetting->created_at->toISOString(),
                    'updatedAt' => $quizSetting->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Quizzes retrieved successfully!',
                'data' => $formattedQuizzes
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


        public function fetch($content_id)
    {
        try {
            $user = Auth::user();

            $quizSetting = QuizSetting::with(['questions'])
                ->where('content_id', $content_id)
                ->first();

            if (!$quizSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'No quiz found for this content.'
                ], 404);
            }

            // Format response for single quiz
            $formattedQuiz = [
                'id' => $quizSetting->id,
                'title' => $quizSetting->title,
                'description' => $quizSetting->description,
                'content_id' => $quizSetting->content_id,
                'timeLimit' => $quizSetting->time_limit,
                'attempts' => $quizSetting->attempts,
                'passingScore' => $quizSetting->passing_score,
                'settings' => $quizSetting->settings,
                'questions' => $quizSetting->questions->map(function ($question) {
                    return [
                        'id' => $question->uuid,
                        'type' => $question->type,
                        'question' => $question->question,
                        'points' => $question->points,
                        'correctAnswer' => $question->correct_answer,
                        'explanation' => $question->explanation,
                        'options' => $question->options,
                        'required' => $question->required,
                    ];
                }),
                'courseId' => $quizSetting->course_id,
                'createdAt' => $quizSetting->created_at->toISOString(),
                'updatedAt' => $quizSetting->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Quiz retrieved successfully!',
                'data' => $formattedQuiz
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $quizSetting = QuizSetting::find($id);

            if (!$quizSetting) {
                return response()->json([
                    'status' => false,
                    'message' => 'Quiz not found or access denied.'
                ], 404);
            }

            // Find and delete the associated content record
            $content = Content::where('contentable_id', $quizSetting->id)
                ->where('contentable_type', QuizSetting::class)
                ->first();

            if ($content) {
                $content->delete();
            }

            // Delete related questions
            QuizQuestions::where('quiz_setting_id', $quizSetting->id)->delete();

            // Delete the quiz setting
            $quizSetting->delete();

            return response()->json([
                'status' => true,
                'message' => 'Quiz and associated content deleted successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
