<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Content;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function create(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'title' => 'required',
                // 'description' => 'required',
                // 'price' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }
            $data = $request->except(['file', 'image']);
            $data['user_id'] = $data['instructor_id'] = $user->uuid;
            $data['uuid'] = Str::uuid();
            if ($request->has('image') && $request->image !== null) {
                $image = $request->image;
                $imageName = $image->hashName();
                $image->move(public_path('/courseImages'), $imageName);
                $data['image'] = $imageName;
            }
            $data['course_code'] = strtoupper(substr($request->title, 0, 3)) . '-' . Str::upper(Str::random(5));

            $course = Course::create($data);
            return response()->json([
                'status' => true,
                'data' => $course,
                'message' => 'Course Created Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
    public function createSection(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'course_id' => 'required',
                //  'contentType'=> 'text'


            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }
            $data = $request->all();
            $section = Section::create($data);
            return response()->json([
                'status' => true,
                'data' => $section,
                'message' => 'Section Created Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function fetchSection($sectionId)
    {
        try {
            $section = Section::with('contents')->findOrFail($sectionId);

            return response()->json([
                'status' => true,
                'data' => $section,
                'message' => 'Section Details Fetched Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function updateSection(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'section_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }



            $data = collect($request->all())->except(['section_id'])->toArray();
            $data['id'] = $request->section_id;
            $data = array_filter($data, function ($value) {
                return !is_null($value) && $value !== '';
            });

            $section = Section::findOrFail($request->section_id);
            $section->update($data);

            return response()->json([
                'status' => true,
                'data' => $section->refresh(),
                'message' => 'Section Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function deleteSection($sectionId)
    {
        try {
            $section = Section::findOrFail($sectionId);
            $section->delete();

            return response()->json([
                'status' => true,
                'message' => 'Section Deleted Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }


    public function createContent(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [

                'course_id' => 'required|exists:courses,uuid',
                'contentType' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }
            $data = $request->all();
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = $file->hashName();
                $file->move(public_path('/contentFiles'), $fileName);
                $data['file'] = $fileName; // Store file path in data field
            }
            $content = Content::create($data);
            return response()->json([
                'status' => true,
                'data' => $content,
                'message' => 'Content Created Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function oldfetchContent($contentId)
    {
        try {
            $content = Content::findOrFail($contentId);

            return response()->json([
                'status' => true,
                'data' => $content,
                'message' => 'Content Details Fetched Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function fetchContent($contentId)
{
    try {
        $content = Content::with('contentable')->findOrFail($contentId);
        
        // return true;
        $response = [
            'id' => $content->id,
            'title' => $content->title,
            'description' => $content->description,
            'contentType' => $content->contentType,
            'courseId' => $content->course_id,
            'sectionId' => $content->section_id,
            // 'data' => $content->data,
        ];

        // Add the specific entity data based on content type
        if ($content->contentable) {
            switch ($content->contentType) {
                case 'assignment':
                    $assignment = $content->contentable;
                    $assignment->load(['rubrics.levels', 'resources']);
                    $response['assignment'] = [
                        'id' => $assignment->uuid,
                        'title' => $assignment->title,
                        'description' => $assignment->description,
                        'instructions' => $assignment->instructions,
                        'dueDate' => $assignment->due_date?->toISOString(),
                        'points' => $assignment->points,
                        'submissionType' => $assignment->submission_type,
                        'allowedFileTypes' => $assignment->allowed_file_types,
                        'maxFileSize' => $assignment->max_file_size,
                        'attempts' => $assignment->attempts,
                        'status' => $assignment->status,
                        'rubric' => $assignment->rubrics->map(function ($rubric) {
                            return [
                                'id' => $rubric->uuid,
                                'name' => $rubric->name,
                                'description' => $rubric->description,
                                'levels' => $rubric->levels->map(function ($level) {
                                    return [
                                        'id' => $level->uuid,
                                        'name' => $level->name,
                                        'points' => $level->points,
                                        'description' => $level->description,
                                    ];
                                }),
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
                    ];
                    break;

                case 'quiz':
                    $quiz = $content->contentable;
                    $quiz->load('questions');
                    $response['quiz'] = [
                        'id' => $quiz->uuid,
                        'title' => $quiz->title,
                        'description' => $quiz->description,
                        'timeLimit' => $quiz->time_limit,
                        'attempts' => $quiz->attempts,
                        'passingScore' => $quiz->passing_score,
                        'settings' => $quiz->settings,
                        'status' => $quiz->status,
                        'questions' => $quiz->questions->map(function ($question) {
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
                    ];
                    break;

                case 'survey':
                    $survey = $content->contentable;
                    $survey->load('questions');
                    $response['survey'] = [
                        'id' => $survey->uuid,
                        'title' => $survey->title,
                        'description' => $survey->description,
                        'anonymous' => $survey->anonymous,
                        'allowMultipleResponses' => $survey->allow_multiple_responses,
                        'showResults' => $survey->show_results,
                        'status' => $survey->status,
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
                    ];
                    break;

                default:
                    // For other content types (file, video, etc.)
                    $response['data'] = $content->data;
                    $response['file'] = $content->file;
                    break;
            }
        }

        return response()->json([
            'status' => true,
            'data' => $response,
            'message' => 'Content Details Fetched Successfully!'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 404);
    }
}
    public function fetchContentData($contentId)
{
    try {
        $content = Content::with('contentable')->findOrFail($contentId);
        
        // return true;
        $response = [
            'id' => $content->id,
            'title' => $content->title,
            'description' => $content->description,
            'contentType' => $content->contentType,
            'courseId' => $content->course_id,
            'sectionId' => $content->section_id,
            'data' => $content->data,
        ];

        // Add the specific entity data based on content type
        if ($content->contentable) {
            switch ($content->contentType) {
                case 'assignment':
                    $assignment = $content->contentable;
                    $assignment->load(['rubrics.levels', 'resources']);
                    $response['assignment'] = [
                        'id' => $assignment->uuid,
                        'title' => $assignment->title,
                        'description' => $assignment->description,
                        'instructions' => $assignment->instructions,
                        'dueDate' => $assignment->due_date?->toISOString(),
                        'points' => $assignment->points,
                        'submissionType' => $assignment->submission_type,
                        'allowedFileTypes' => $assignment->allowed_file_types,
                        'maxFileSize' => $assignment->max_file_size,
                        'attempts' => $assignment->attempts,
                        'status' => $assignment->status,
                        'rubric' => $assignment->rubrics->map(function ($rubric) {
                            return [
                                'id' => $rubric->uuid,
                                'name' => $rubric->name,
                                'description' => $rubric->description,
                                'levels' => $rubric->levels->map(function ($level) {
                                    return [
                                        'id' => $level->uuid,
                                        'name' => $level->name,
                                        'points' => $level->points,
                                        'description' => $level->description,
                                    ];
                                }),
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
                    ];
                    break;

                case 'quiz':
                    $quiz = $content->contentable;
                    $quiz->load('questions');
                    $response['quiz'] = [
                        'id' => $quiz->uuid,
                        'title' => $quiz->title,
                        'description' => $quiz->description,
                        'timeLimit' => $quiz->time_limit,
                        'attempts' => $quiz->attempts,
                        'passingScore' => $quiz->passing_score,
                        'settings' => $quiz->settings,
                        'status' => $quiz->status,
                        'questions' => $quiz->questions->map(function ($question) {
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
                    ];
                    break;

                case 'survey':
                    $survey = $content->contentable;
                    $survey->load('questions');
                    $response['survey'] = [
                        'id' => $survey->uuid,
                        'title' => $survey->title,
                        'description' => $survey->description,
                        'anonymous' => $survey->anonymous,
                        'allowMultipleResponses' => $survey->allow_multiple_responses,
                        'showResults' => $survey->show_results,
                        'status' => $survey->status,
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
                    ];
                    break;

                default:
                    // For other content types (file, video, etc.)
                    $response['data'] = $content->data;
                    $response['file'] = $content->file;
                    break;
            }
        }

        return response()->json([
            'status' => true,
            'data' => $response,
            'message' => 'Content Details Fetched Successfully!'
        ], 200);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 404);
    }
}

    public function updateContent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'content_id' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }

            $data = $request->all();
            $data = array_filter($data, function ($value) {
                return !is_null($value) && $value !== '';
            });

            $content = Content::findOrFail($request->content_id);
            $data = collect($data)->except(['content_id'])->toArray();
            $data['id'] = $request->content_id;
            $content->update($data);

            return response()->json([
                'status' => true,
                'data' => $content->refresh(),
                'message' => 'Content Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function deleteContent($contentId)
    {
        try {
            $content = Content::findOrFail($contentId);
            $content->delete();

            return response()->json([
                'status' => true,
                'message' => 'Content Deleted Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function fetchCourseContent(Request $request)
    {
        $courseId   = $request->input('course_id');
        $sectionId  = $request->input('section_id');
        $contentId  = $request->input('content_id');




        // Base query for Content model
        $query = Content::query();

        if ($contentId && $sectionId && $courseId) {
            $query->where('id', $contentId)
                ->where('section_id', $sectionId)
                ->where('course_id', $courseId);
        } elseif ($contentId && $sectionId) {
            $query->where('id', $contentId)
                ->where('section_id', $sectionId);
        } elseif ($contentId && $courseId) {
            $query->where('id', $contentId)
                ->where('course_id', $courseId);
        } elseif ($sectionId && $courseId) {
            $query->where('section_id', $sectionId)
                ->where('course_id', $courseId);
        } elseif ($contentId) {
            $query->where('id', $contentId);
        } elseif ($sectionId) {
            $query->where('section_id', $sectionId);
        } elseif ($courseId) {
            $course = Course::with(['base_contents', 'sections' => function ($query) {
                $query->with('contents');
            }])->where('uuid', $courseId)->firstOrFail();

            return response()->json([
                'status' => true,
                'data' => $course,
                'message' => 'Content(s) fetched successfully.'
            ], 200);
            // $query->where('course_id', $courseId);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'At least one of course_id, section_id or content_id is required.',
            ], 400);
        }

        $contents = $query->get();

        return response()->json([
            'status' => true,
            'data' => $contents,
            'message' => 'Content(s) fetched successfully.'
        ], 200);
    }




    public function     saveCourseContent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|string',
                'sections' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $courseData = $request->all();
            $courseId = $courseData['course_id'];


            // Update course
            if (isset($courseData['data']) && is_string($courseData['data'])) {
                $cleanedData = trim($courseData['data'], "'"); // Remove any surrounding quotes/apostrophes
                $courseData = json_decode($cleanedData, true);
            }
            $course = Course::where('uuid', $courseId)->firstOrFail();

            $course->update(collect($courseData)->except(['sections', 'id', 'created_at', 'updated_at'])->toArray());

            if (isset($courseData['sections'])) {
                // Get existing section IDs for this course
                $existingSectionIds = Section::where('course_id', $course->id)->pluck('id')->toArray();
                $updatedSectionIds = [];

                foreach ($courseData['sections'] as $sectionData) {
                    $section = Section::updateOrCreate(
                        ['id' => $sectionData['id'] ?? null, 'course_id' => $course->uuid], // Ensure correct course_id
                        collect($sectionData)->except(['contents', 'created_at', 'updated_at'])->toArray()
                    );

                    $updatedSectionIds[] = $section->id;

                    if (isset($sectionData['contents'])) {
                        // Get existing content IDs for this section
                        $existingContentIds = Content::where('section_id', $section->id)->where('course_id', $course->uuid)->pluck('id')->toArray();
                        $updatedContentIds = [];

                        foreach ($sectionData['contents'] as $contentData) {
                            $content = Content::updateOrCreate(
                                ['id' => $contentData['id'] ?? null, 'section_id' => $section->id, 'course_id' => $course->uuid], // Ensure correct section_id
                                collect($contentData)->except(['created_at', 'updated_at'])->toArray()
                            );
                            $updatedContentIds[] = $content->id;
                        }

                        // Delete contents that are no longer in the updated data
                        Content::where('section_id', $section->id)
                            ->where('course_id', $course->uuid)
                            ->whereNotIn('id', $updatedContentIds)
                            ->delete();
                    }
                }

                // Delete sections that are no longer in the updated data
                Section::where('course_id', $course->id)
                    ->whereNotIn('id', $updatedSectionIds)
                    ->delete();
            }

            // Fetch updated course with relations
            $updatedCourse = Course::with(['sections' => function ($query) {
                $query->with('contents');
            }])->where('uuid', $courseId)->firstOrFail();

            return response()->json([
                'status' => true,
                'data' => $updatedCourse,
                'message' => 'Course Content Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function newsaveCourseContent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required|string',
                'sections' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $courseData = $request->all();
            $courseId = $courseData['course_id'];

            // Parse JSON data if it exists
            if (isset($courseData['data']) && is_string($courseData['data'])) {
                $cleanedData = trim($courseData['data'], "'");
                $courseData = json_decode($cleanedData, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Invalid JSON data: ' . json_last_error_msg()
                    ], 400);
                }
            }

            $course = Course::where('uuid', $courseId)->firstOrFail();
            $course->update(collect($courseData)->except(['sections', 'id', 'created_at', 'updated_at'])->toArray());

            if (isset($courseData['sections'])) {
                $updatedSectionIds = [];

                foreach ($courseData['sections'] as $sectionData) {
                    if (isset($sectionData['id'])) {
                        // Update existing section
                        $section = Section::where('id', $sectionData['id'])
                            ->where('course_id', $course->id)
                            ->firstOrFail();
                        $section->update(collect($sectionData)
                            ->except(['contents', 'created_at', 'updated_at'])
                            ->toArray());
                    } else {
                        // Create new section
                        $section = Section::create(array_merge(
                            collect($sectionData)
                                ->except(['contents', 'created_at', 'updated_at'])
                                ->toArray(),
                            ['course_id' => $course->id]
                        ));
                    }

                    $updatedSectionIds[] = $section->id;

                    // Handle contents for this section
                    if (isset($sectionData['contents'])) {
                        $updatedContentIds = [];

                        foreach ($sectionData['contents'] as $contentData) {
                            if (isset($contentData['id'])) {
                                // Update existing content
                                $content = Content::where('id', $contentData['id'])
                                    ->where('section_id', $section->id)
                                    ->firstOrFail();
                                $content->update(collect($contentData)
                                    ->except(['created_at', 'updated_at'])
                                    ->toArray());
                            } else {
                                // Create new content
                                $content = Content::create(array_merge(
                                    collect($contentData)
                                        ->except(['created_at', 'updated_at'])
                                        ->toArray(),
                                    [
                                        'section_id' => $section->id,
                                        'course_id' => $course->id
                                    ]
                                ));
                            }
                            $updatedContentIds[] = $content->id;
                        }

                        // Delete contents that are no longer in the updated data
                        Content::where('section_id', $section->id)
                            ->whereNotIn('id', $updatedContentIds)
                            ->delete();
                    }
                }

                // Delete sections that are no longer in the updated data
                Section::where('course_id', $course->id)
                    ->whereNotIn('id', $updatedSectionIds)
                    ->delete();
            }

            // Fetch updated course with relations
            $updatedCourse = Course::with(['sections' => function ($query) {
                $query->with('contents');
            }])->where('uuid', $courseId)->firstOrFail();

            return response()->json([
                'status' => true,
                'data' => $updatedCourse,
                'message' => 'Course Content Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    // ... existing code ...
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'course_id' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }

            $user = Auth::user();


            $data = $request->except(['file', 'image']);
            $data = array_filter($data, function ($value) {
                return !is_null($value) && $value !== '';
            });

            $course = Course::where('uuid', $request->course_id)->firstOrFail();

            $data['user_id'] = $data['instructor_id'] = $user->uuid;
            if ($request->has('image') && $request->image !== null) {
                // Check if there is an existing image and delete it
                $existingImage = $course->image; // Assuming $course is your model instance
                if ($existingImage && file_exists(public_path('/courseImages/' . $existingImage))) {
                    unlink(public_path('/courseImages/' . $existingImage));
                }

                // Upload the new image
                $image = $request->image;
                $imageName = $image->hashName();
                $image->move(public_path('courseImages'), $imageName);
                $data['image'] = $imageName;
            }

            $data = array_filter($data, function ($value) {
                return !is_null($value);
            });


            $course->update($data);
            return response()->json([
                'status' => true,
                'data' => $course->refresh(),
                'message' => 'Course Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function view($courseId)
    {

        $course = Course::where('uuid', $courseId)->firstOrFail();
        return response()->json([
            'status' => true,
            'data' => $course,
            'message' => 'Course Details Fetched Successfully!'
        ], 200);
    }
    public function allcourses($userID)
    {
        $course = Course::where('user_id', $userID)->get();

        if (!$course) {
            return response()->json([
                'status' => false,
                'data' => null,
                'message' => 'No courses found for the given user ID.',
            ], 404);
        }
        return response()->json([
            'status' => true,
            'data' => $course,
            'message' => 'Course details fetched successfully!',
        ], 200);
    }

    public function delete($courseId)
    {

        $course = Course::where('uuid', $courseId)->firstOrFail();
        //delete all resources, and files associated to the course
        $course->delete();
        return response()->json([
            'status' => true,
            'message' => 'Course Deleted Successfully!'
        ], 200);
    }
}
