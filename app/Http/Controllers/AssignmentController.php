<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Content;
use App\Models\Resource;
use App\Models\Rubric;
use App\Models\RubricLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
                'rubric' => 'nullable|array',
                'rubric.*.name' => 'nullable|string|max:255',
                'rubric.*.description' => 'nullable|string',
                'rubric.*.levels' => 'nullable|array',
                'rubric.*.levels.*.name' => 'nullable|string|max:255',
                'rubric.*.levels.*.points' => 'nullable|integer',
                'rubric.*.levels.*.description' => 'nullable|string',
                // 'rubric' => 'sometimes|array|min:1',
                // 'rubric.*.name' => 'sometimes|string|max:255',
                // 'rubric.*.description' => 'sometimes|string',
                // 'rubric.*.levels' => 'sometimes|array|min:1',
                // 'rubric.*.levels.*.name' => 'sometimes|string|max:255',
                // 'rubric.*.levels.*.points' => 'sometimes|integer|min:0',
                // 'rubric.*.levels.*.description' => 'sometimes|string',

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
                'user_id' => $user->id,
                'course_uuid' => $validated['course_uuid'],
                'content_id' => $validated['content_id'],
                'uuid' => Str::uuid(),
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

            $content = Content::find($validated['content_id']);
            if ($content) {
                $content->update([
                    'contentable_id' => $assignment->id,
                    'contentable_type' => Assignment::class,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'contentType' => 'assignment'
                ]);
            }
            // Handle Rubrics (mandatory)
            if (isset($validated['rubric'])) {
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
            }

            // Handle Resources (optional)
            if (!empty($validated['resources'])) {
                foreach ($validated['resources'] as $resourceData) {
                    Resource::create([
                        'uuid' => Str::uuid(),
                        'assignment_id' => $assignment->id,
                        'name' =>  $resourceData['name'],
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


    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $assignment = Assignment::where('content_id',$id);

            if (!$assignment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'course_uuid' => 'sometimes|string|exists:courses,uuid',
                'content_id' => 'sometimes|required',
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'instructions' => 'sometimes|string',
                'due_date' => 'sometimes|date',
                'points' => 'sometimes|integer|min:0',
                'submission_type' => 'sometimes|in:file,text,link,both',
                'allowed_file_types' => 'sometimes|array',
                'max_file_size' => 'sometimes|integer|min:1',
                'attempts' => 'sometimes|integer|min:1',
                'status' => 'sometimes|in:draft,published,archived',

                // Rubric updates
                'rubric' => 'nullable|array',
                'rubric.*.id' => 'nullable|exists:rubric,id',
                'rubric.*.name' => 'required_with:rubric|string|max:255',
                'rubric.*.description' => 'required_with:rubric|string',
                'rubric.*.levels' => 'required_with:rubric|array',
                'rubric.*.levels.*.id' => 'nullable|exists:rubric_level,id',
                'rubric.*.levels.*.name' => 'required_with:rubric.*.levels|string|max:255',
                'rubric.*.levels.*.points' => 'required_with:rubric.*.levels|integer',
                'rubric.*.levels.*.description' => 'required_with:rubric.*.levels|string',

                // Resources updates
                'resources' => 'nullable|array',
                'resources.*.id' => 'nullable|exists:resource,id',
                'resources.*.name' => 'required_with:resources|string',
                'resources.*.type' => 'nullable|in:link,file',
                'resources.*.url' => 'required_with:resources|string',
                'resources.*.description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Update assignment
            $assignment->update(collect($validated)->except(['rubric', 'resources'])->toArray());

            // Handle Rubrics update
            if (isset($validated['rubric'])) {
                $updatedRubricIds = [];

                foreach ($validated['rubric'] as $rubricData) {
                    if (isset($rubricData['id'])) {
                        // Update existing rubric
                        $rubric = Rubric::where('id', $rubricData['id'])
                            ->where('assignment_id', $assignment->id)
                            ->first();
                        if ($rubric) {
                            $rubric->update([
                                'name' => $rubricData['name'],
                                'description' => $rubricData['description'],
                            ]);
                        }
                    } else {
                        // Create new rubric
                        $rubric = Rubric::create([
                            'uuid' => Str::uuid(),
                            'assignment_id' => $assignment->id,
                            'name' => $rubricData['name'],
                            'description' => $rubricData['description'],
                        ]);
                    }

                    $updatedRubricIds[] = $rubric->id;

                    // Handle rubric levels
                    if (isset($rubricData['levels'])) {
                        $updatedLevelIds = [];

                        foreach ($rubricData['levels'] as $levelData) {
                            if (isset($levelData['id'])) {
                                // Update existing level
                                $level = RubricLevel::where('id', $levelData['id'])
                                    ->where('rubric_id', $rubric->id)
                                    ->first();
                                if ($level) {
                                    $level->update([
                                        'name' => $levelData['name'],
                                        'points' => $levelData['points'],
                                        'description' => $levelData['description'],
                                    ]);
                                }
                            } else {
                                // Create new level
                                $level = RubricLevel::create([
                                    'uuid' => Str::uuid(),
                                    'rubric_id' => $rubric->id,
                                    'name' => $levelData['name'],
                                    'points' => $levelData['points'],
                                    'description' => $levelData['description'],
                                ]);
                            }
                            $updatedLevelIds[] = $level->id;
                        }

                        // Delete levels not in update
                        RubricLevel::where('rubric_id', $rubric->id)
                            ->whereNotIn('id', $updatedLevelIds)
                            ->delete();
                    }
                }

                // Delete rubrics not in update
                Rubric::where('assignment_id', $assignment->id)
                    ->whereNotIn('id', $updatedRubricIds)
                    ->delete();
            }

            // Handle Resources update
            if (isset($validated['resources'])) {
                $updatedResourceIds = [];

                foreach ($validated['resources'] as $resourceData) {
                    if (isset($resourceData['id'])) {
                        // Update existing resource
                        $resource = Resource::where('id', $resourceData['id'])
                            ->where('assignment_id', $assignment->id)
                            ->first();
                        if ($resource) {
                            $resource->update([
                                'name' => $resourceData['name'],
                                'type' => $resourceData['type'] ?? 'file',
                                'url' => $resourceData['url'],
                                'description' => $resourceData['description'] ?? null,
                            ]);
                        }
                    } else {
                        // Create new resource
                        $resource = Resource::create([
                            'uuid' => Str::uuid(),
                            'assignment_id' => $assignment->id,
                            'name' => $resourceData['name'],
                            'type' => $resourceData['type'] ?? 'file',
                            'url' => $resourceData['url'],
                            'description' => $resourceData['description'] ?? null,
                        ]);
                    }
                    $updatedResourceIds[] = $resource->id;
                }

                // Delete resources not in update
                Resource::where('assignment_id', $assignment->id)
                    ->whereNotIn('id', $updatedResourceIds)
                    ->delete();
            }

            return response()->json([
                'status' => true,
                'message' => 'Assignment updated successfully!',
                'data' => $assignment->with(['course', 'rubrics.levels', 'resources'])->first()
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
            $assignment = Assignment::with(['course', 'rubrics.levels', 'resources'])
                ->where('id', $id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment not found .'
                ], 404);
            }

            // Format response to match requirements
            $formattedAssignment = [
                'id' => $assignment->uuid,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions,
                'dueDate' => $assignment->due_date ? $assignment->due_date->toISOString() : null,
                'points' => $assignment->points,
                'submissionType' => $assignment->submission_type,
                'allowedFileTypes' => $assignment->allowed_file_types,
                'maxFileSize' => $assignment->max_file_size,
                'attempts' => $assignment->attempts,
                'rubric' => $assignment->rubrics->map(function ($rubric) {
                    return [
                        'id' => $rubric->uuid,
                        'name' => $rubric->name,
                        'description' => $rubric->description,
                        'points' => $rubric->levels->sum('points'),
                        'levels' => $rubric->levels->map(function ($level) {
                            return [
                                'name' => $level->name,
                                'points' => $level->points,
                                'description' => $level->description,
                            ];
                        })
                    ];
                }),
                'resources' => $assignment->resources->map(function ($resource) {
                    return [
                        'id' => $resource->uuid,
                        'name' => $resource->name,
                        'type' => $resource->type,
                        'url' => $resource->url,
                        'description' => $resource->description,
                    ];
                }),
                'courseId' => $assignment->course_uuid,
                'createdAt' => $assignment->created_at->toISOString(),
                'updatedAt' => $assignment->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Assignment retrieved successfully!',
                'data' => $formattedAssignment
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function oldfetch($id)
    {
        try {
            $user = Auth::user();

            $assignments = Assignment::with(['course', 'rubrics.levels', 'resources'])
                ->find($id)
                ->get();

            if ($assignments->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No assignments found for this content.'
                ], 404);
            }

            // Format response for all assignments
            $formattedAssignments = $assignments->map(function ($assignment) {
                return [
                    'id' => $assignment->uuid,
                    'title' => $assignment->title,
                    'description' => $assignment->description,
                    'instructions' => $assignment->instructions,
                    'content_id' => $assignment->content_id,
                    'dueDate' => $assignment->due_date ? $assignment->due_date->toISOString() : null,
                    'points' => $assignment->points,
                    'submissionType' => $assignment->submission_type,
                    'allowedFileTypes' => $assignment->allowed_file_types,
                    'maxFileSize' => $assignment->max_file_size,
                    'attempts' => $assignment->attempts,
                    'rubric' => $assignment->rubrics->map(function ($rubric) {
                        return [
                            'id' => $rubric->uuid,
                            'name' => $rubric->name,
                            'description' => $rubric->description,
                            'points' => $rubric->levels->sum('points'),
                            'levels' => $rubric->levels->map(function ($level) {
                                return [
                                    'name' => $level->name,
                                    'points' => $level->points,
                                    'description' => $level->description,
                                ];
                            })
                        ];
                    }),
                    'resources' => $assignment->resources->map(function ($resource) {
                        return [
                            'id' => $resource->uuid,
                            'name' => $resource->name,
                            'type' => $resource->type,
                            'url' => $resource->url,
                            'description' => $resource->description,
                        ];
                    }),
                    'courseId' => $assignment->course_uuid,
                    'createdAt' => $assignment->created_at->toISOString(),
                    'updatedAt' => $assignment->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Assignments retrieved successfully!',
                'data' => $formattedAssignments
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

            $assignment = Assignment::with(['course', 'rubrics.levels', 'resources'])
                ->where('content_id', $content_id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'status' => false,
                    'message' => 'No assignment found with this content.'
                ], 404);
            }

            // Format response for single assignment
            $formattedAssignment = [
                'id' => $assignment->uuid,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'instructions' => $assignment->instructions,
                'content_id' => $assignment->content_id,
                'dueDate' => $assignment->due_date ? $assignment->due_date->toISOString() : null,
                'points' => $assignment->points,
                'submissionType' => $assignment->submission_type,
                'allowedFileTypes' => $assignment->allowed_file_types,
                'maxFileSize' => $assignment->max_file_size,
                'attempts' => $assignment->attempts,
                'rubric' => $assignment->rubrics->map(function ($rubric) {
                    return [
                        'id' => $rubric->uuid,
                        'name' => $rubric->name,
                        'description' => $rubric->description,
                        'points' => $rubric->levels->sum('points'),
                        'levels' => $rubric->levels->map(function ($level) {
                            return [
                                'name' => $level->name,
                                'points' => $level->points,
                                'description' => $level->description,
                            ];
                        })
                    ];
                }),
                'resources' => $assignment->resources->map(function ($resource) {
                    return [
                        'id' => $resource->uuid,
                        'name' => $resource->name,
                        'type' => $resource->type,
                        'url' => $resource->url,
                        'description' => $resource->description,
                    ];
                }),
                'courseId' => $assignment->course_uuid,
                'createdAt' => $assignment->created_at->toISOString(),
                'updatedAt' => $assignment->updated_at->toISOString(),
            ];

            return response()->json([
                'status' => true,
                'message' => 'Assignment retrieved successfully!',
                'data' => $formattedAssignment
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
            $assignment = Assignment::find($id);

            if (!$assignment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment not found .'
                ], 404);
            }

            // Delete related rubrics and their levels (cascade)
            $rubrics = Rubric::where('assignment_id', $assignment->id)->get();
            foreach ($rubrics as $rubric) {
                RubricLevel::where('rubric_id', $rubric->id)->delete();
                $rubric->delete();
            }

            // Delete related resources
            Resource::where('assignment_id', $assignment->id)->delete();

            // Delete the assignment
            $assignment->delete();

            return response()->json([
                'status' => true,
                'message' => 'Assignment deleted successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
