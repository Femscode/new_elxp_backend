<?php

namespace App\Http\Controllers;

use App\Models\QuizSetting;
use App\Models\QuizQuestions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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
                                                            $fail($attribute.' must be a string, number, or boolean.');
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
                'user_id'=> $user->id,
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
}
