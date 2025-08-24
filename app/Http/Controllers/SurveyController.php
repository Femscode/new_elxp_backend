<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function create(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate incoming request
            $validator = Validator::make($request->all(), [
                // Survey fields
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'course_id' => 'required|exists:courses,id',
                'status' => 'in:draft,published,archived',
                'anonymous' => 'boolean',
                'allow_multiple_responses' => 'boolean',
                'show_results' => 'boolean',

                // Questions
                'questions' => 'required|array|min:1',
                'questions.*.type' => 'required|string|in:multiple-choice,checkbox,rating,text,textarea,likert',
                'questions.*.question' => 'required|string',
                'questions.*.textAnswer' => 'nullable|string|required_if:questions.*.type,text|required_if:questions.*.type,textarea',
                'questions.*.scale' => 'nullable|array',
                'questions.*.scale.min' => 'required_with:questions.*.scale|integer',
                'questions.*.scale.max' => 'required_with:questions.*.scale|integer|gte:questions.*.scale.min',
                'questions.*.scale.minLabel' => 'required_with:questions.*.scale|string',
                'questions.*.scale.maxLabel' => 'required_with:questions.*.scale|string',
                'questions.*.options' => 'nullable|array',
                'questions.*.likert_options'=>'nullable|array',
                'questions.*.required' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Create Survey
            $survey = Survey::create([
                'user_id'=> $user->id,
                'uuid' => Str::uuid(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'course_id' => $validated['course_id'],
                'status' => $validated['status'] ?? 'draft',
                'anonymous' => $validated['anonymous'] ?? false,
                'allow_multiple_responses' => $validated['allow_multiple_responses'] ?? false,
                'show_results' => $validated['show_results'] ?? false,
            ]);

            // Create Survey Questions
            foreach ($validated['questions'] as $q) {
                SurveyQuestion::create([
                    'uuid' => Str::uuid(),
                    'survey_id' => $survey->id,
                    'type' => $q['type'],
                    'question' => $q['question'],
                    'textAnswer'=>$q['textAnswer'] ?? null,
                    'scale' => $q['scale'] ?? [],
                    'options' => $q['options'] ?? [],
                    'likert_options'=>$q['likert_options']??[],
                    'required' => $q['required'] ?? false,
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Survey created successfully!',
                'data' => $survey->load('questions')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
