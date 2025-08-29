<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Rubric;
use App\Models\RubricLevel;
use App\Models\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{

    public function create(Request $request)
    {
        try {
            $user = Auth::user();
            $validator = Validator::make($request->all(), [
                'course_uuid' => 'required|string|exists:courses,uuid',
                'content_id' => 'required',
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'instructions' => 'required|string',
                'due_date' => 'required|date',
                'points' => 'integer|min:0',
                'submission_type' => 'in:file,text,link',
                'allowed_file_types' => 'required|array',
                'max_file_size' => 'required|integer|min:1',
                'attempts' => 'required|integer|min:1',
                'status' => 'in:draft,published,archived',

                // Rubric is required
                'rubric' => 'required|array|min:1',
                'rubric.*.name' => 'required|string|max:255',
                'rubric.*.description' => 'required|string',
                'rubric.*.levels' => 'required|array|min:1',
                'rubric.*.levels.*.name' => 'required|string|max:255',
                'rubric.*.levels.*.points' => 'required|integer|min:0',
                'rubric.*.levels.*.description' => 'required|string',

                // Resources are optional
                'resources' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Create assignment
            $assignment = Assignment::create([
                'user_id'=> $user->id,
                'course_uuid' => $validated['course_uuid'],
                'content_id' => $validated['content_id'],
                'uuid'=>Str::uuid(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'instructions' => $validated['instructions'],
                'due_date' => $validated['due_date'],
                'points' => $validated['points'] ?? 0,
                'submission_type' => $validated['submission_type'] ?? 'file',
                'allowed_file_types' => $validated['allowed_file_types'] ?? [],
                'max_file_size' => $validated['max_file_size'],
                'attempts' => $validated['attempts'] ?? 1,
                'status' => $validated['status'] ?? 'draft',
            ]);

            // Handle Rubrics (mandatory)
            foreach ($validated['rubric'] as $rubricData) {
                $rubric = Rubric::create([
                    'uuid' => Str::uuid(),
                    'assignment_id' => $assignment->id,
                    'name' => $rubricData['name'],
                    'description' => $rubricData['description'],
                ]);

                foreach ($rubricData['levels'] as $levelData) {
                    RubricLevel::create([
                        'uuid' => Str::uuid(),
                        'rubric_id' => $rubric->id,
                        'name' => $levelData['name'],
                        'points' => $levelData['points'],
                        'description' => $levelData['description'],
                    ]);
                }
            }

            // Handle Resources (optional)
            if (!empty($validated['resources'])) {
                foreach ($validated['resources'] as $resourceData) {
                    Resource::create([
                        'uuid' => Str::uuid(),
                        'assignment_id' => $assignment->id,
                        'name'=>  $resourceData['name'],
                        // 'type' => $resourceData['type'] ?? 'file',
                        'url' => $resourceData['url'] ?? null,
                        'description' => $resourceData['description'] ?? null,
                    ]);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Assignment created successfully!',
                'data' => $assignment->load(['course', 'rubrics.levels', 'resources'])
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
