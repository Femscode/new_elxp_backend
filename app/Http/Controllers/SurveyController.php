<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\Content;
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
                'course_id' => 'required|exists:courses,uuid',
                'content_id' => 'required|exists:course_contents,id',
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
                'questions.*.likert_options' => 'nullable|array',
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
                'user_id' => $user->id,
                'uuid' => Str::uuid(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'course_id' => $validated['course_id'],
                'content_id' => $validated['content_id'],
                'status' => $validated['status'] ?? 'draft',
                'anonymous' => $validated['anonymous'] ?? false,
                'allow_multiple_responses' => $validated['allow_multiple_responses'] ?? false,
                'show_results' => $validated['show_results'] ?? false,
            ]);

            $content = Content::find($validated['content_id']);
            if ($content) {
                $content->update([
                    'contentable_id' => $survey->id,
                    'contentable_type' => Survey::class,
                    'title' => $survey->title,
                    'description' => $survey->description,
                    'contentType' => 'survey'
                ]);
            }

            // Create Survey Questions
            foreach ($validated['questions'] as $q) {
                SurveyQuestion::create([
                    'uuid' => Str::uuid(),
                    'survey_id' => $survey->id,
                    'type' => $q['type'],
                    'question' => $q['question'],
                    'textAnswer' => $q['textAnswer'] ?? null,
                    'scale' => $q['scale'] ?? [],
                    'options' => $q['options'] ?? [],
                    'likert_options' => $q['likert_options'] ?? [],
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

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $survey = Survey::where('content_id',$id)->first();

            if (!$survey) {
                return response()->json([
                    'status' => false,
                    'message' => 'Survey not found or access denied.'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'course_id' => 'sometimes|exists:courses,uuid',
                'content_id' => 'sometimes|exists:course_contents,id',
                'status' => 'sometimes|in:draft,published,archived',
                'anonymous' => 'sometimes|boolean',
                'allow_multiple_responses' => 'sometimes|boolean',
                'show_results' => 'sometimes|boolean',

                // Questions
                'questions' => 'sometimes|array|min:1',
                'questions.*.id' => 'sometimes|exists:survey_question,id',
                'questions.*.type' => 'required_with:questions|string|in:multiple-choice,checkbox,rating,text,textarea,likert',
                'questions.*.question' => 'required_with:questions|string',
                'questions.*.textAnswer' => 'sometimes|string',
                'questions.*.scale' => 'sometimes|array',
                'questions.*.scale.min' => 'required_with:questions.*.scale|integer',
                'questions.*.scale.max' => 'required_with:questions.*.scale|integer|gte:questions.*.scale.min',
                'questions.*.scale.minLabel' => 'required_with:questions.*.scale|string',
                'questions.*.scale.maxLabel' => 'required_with:questions.*.scale|string',
                'questions.*.options' => 'sometimes|array',
                'questions.*.likert_options' => 'sometimes|array',
                'questions.*.required' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Update survey
            $survey->update(collect($validated)->except(['questions'])->toArray());

            // Handle Questions update
            if (isset($validated['questions'])) {
                $updatedQuestionIds = [];

                foreach ($validated['questions'] as $questionData) {
                    if (isset($questionData['id'])) {
                        // Update existing question
                        $question = SurveyQuestion::where('id', $questionData['id'])
                            ->where('survey_id', $survey->id)
                            ->first();
                        if ($question) {
                            $question->update([
                                'type' => $questionData['type'],
                                'question' => $questionData['question'],
                                'textAnswer' => $questionData['textAnswer'] ?? null,
                                'scale' => $questionData['scale'] ?? [],
                                'options' => $questionData['options'] ?? [],
                                'likert_options' => $questionData['likert_options'] ?? [],
                                'required' => $questionData['required'] ?? false,
                            ]);
                        }
                    } else {
                        // Create new question
                        $question = SurveyQuestion::create([
                            'uuid' => Str::uuid(),
                            'survey_id' => $survey->id,
                            'type' => $questionData['type'],
                            'question' => $questionData['question'],
                            'textAnswer' => $questionData['textAnswer'] ?? null,
                            'scale' => $questionData['scale'] ?? [],
                            'options' => $questionData['options'] ?? [],
                            'likert_options' => $questionData['likert_options'] ?? [],
                            'required' => $questionData['required'] ?? false,
                        ]);
                    }
                    $updatedQuestionIds[] = $question->id;
                }

                // Delete questions not in update
                SurveyQuestion::where('survey_id', $survey->id)
                    ->whereNotIn('id', $updatedQuestionIds)
                    ->delete();
            }

            return response()->json([
                'status' => true,
                'message' => 'Survey updated successfully!',
                'data' => $survey->fresh()->load('questions')
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
            $survey = Survey::with(['questions'])
                ->where('id', $id)
                ->first();

            if (!$survey) {
                return response()->json([
                    'status' => false,
                    'message' => 'Survey not found or access denied.'
                ], 404);
            }

            // Format response to match requirements
            $formattedSurvey = [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'anonymous' => $survey->anonymous,
                'allowMultipleResponses' => $survey->allow_multiple_responses,
                'showResults' => $survey->show_results,
                'questions' => $survey->questions->map(function ($question) {
                    return [
                        'id' => $question->uuid,
                        'type' => $question->type,
                        'question' => $question->question,
                        'required' => $question->required,
                        'options' => $question->options,
                        'likertOptions' => $question->likert_options,
                        'scale' => $question->scale,
                    ];
                }),
                'courseId' => $survey->course_id,
                'createdAt' => $survey->created_at->toISOString(),
                'updatedAt' => $survey->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Survey retrieved successfully!',
                'data' => $formattedSurvey
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

            $surveys = Survey::with(['questions'])
                ->where('content_id', $content_id)
                ->get();

            if ($surveys->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No surveys found for this content.'
                ], 404);
            }

            // Format response for all surveys
            $formattedSurveys = $surveys->map(function ($survey) {
                return [
                    'id' => $survey->uuid,
                    'title' => $survey->title,
                    'description' => $survey->description,
                    'anonymous' => $survey->anonymous,
                    'allowMultipleResponses' => $survey->allow_multiple_responses,
                    'showResults' => $survey->show_results,
                    'questions' => $survey->questions->map(function ($question) {
                        return [
                            'id' => $question->uuid,
                            'type' => $question->type,
                            'question' => $question->question,
                            'required' => $question->required,
                            'options' => $question->options,
                            'likertOptions' => $question->likert_options,
                            'scale' => $question->scale,
                        ];
                    }),
                    'courseId' => $survey->course_id,
                    'createdAt' => $survey->created_at->toISOString(),
                    'updatedAt' => $survey->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Surveys retrieved successfully!',
                'data' => $formattedSurveys
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

            $survey = Survey::with(['questions'])
                ->where('content_id', $content_id)
                ->first();

            if (!$survey) {
                return response()->json([
                    'status' => false,
                    'message' => 'No survey found for this content.'
                ], 404);
            }

            // Format response for single survey
            $formattedSurvey = [
                'id' => $survey->id,
                'title' => $survey->title,
                'description' => $survey->description,
                'content_id' => $survey->content_id,
                'anonymous' => $survey->anonymous,
                'allowMultipleResponses' => $survey->allow_multiple_responses,
                'showResults' => $survey->show_results,
                'questions' => $survey->questions->map(function ($question) {
                    return [
                        'id' => $question->uuid,
                        'type' => $question->type,
                        'question' => $question->question,
                        'required' => $question->required,
                        'options' => $question->options,
                        'likertOptions' => $question->likert_options,
                        'scale' => $question->scale,
                    ];
                }),
                'courseId' => $survey->course_id,
                'createdAt' => $survey->created_at->toISOString(),
                'updatedAt' => $survey->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Survey retrieved successfully!',
                'data' => $formattedSurvey
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
            $survey = Survey::find($id);

            if (!$survey) {
                return response()->json([
                    'status' => false,
                    'message' => 'Survey not found or access denied.'
                ], 404);
            }

            // Find and delete the associated content record
            $content = Content::where('contentable_id', $survey->id)
                ->where('contentable_type', Survey::class)
                ->first();

            if ($content) {
                $content->delete();
            }

            // Delete related questions
            SurveyQuestion::where('survey_id', $survey->id)->delete();

            // Delete the survey
            $survey->delete();

            return response()->json([
                'status' => true,
                'message' => 'Survey and associated content deleted successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
