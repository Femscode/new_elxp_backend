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

                'course_id' => 'required',
                'section_id' => 'required',
                'contentType' => 'required',
                'data' => 'required'

            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }
            $data = $request->all();
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

    public function fetchCourseContent($courseId)
    {
        $course = Course::with(['sections' => function ($query) {
            $query->with('contents');
        }])->where('uuid', $courseId)->firstOrFail();

        return response()->json([
            'status' => true,
            'data' => $course,
            'message' => 'Course Details Fetched Successfully!'
        ], 200);
    }


    public function oldsaveCourseContent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [

                'data' => 'required|array',
                'data.uuid' => 'required|string',
                'data.sections' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $courseData = $request->data;
            $courseId = $courseData['uuid'];

            // Update course
            $course = Course::where('uuid', $courseId)->firstOrFail();
            $course->update(collect($courseData)->except(['sections', 'id', 'created_at', 'updated_at'])->toArray());

            if (isset($courseData['sections'])) {
                foreach ($courseData['sections'] as $sectionData) {
                    $section = Section::updateOrCreate(
                        ['id' => $sectionData['id'] ?? null],
                        collect($sectionData)->except(['contents', 'created_at', 'updated_at'])->toArray()
                    );

                    if (isset($sectionData['contents'])) {
                        foreach ($sectionData['contents'] as $contentData) {
                            Content::updateOrCreate(
                                ['id' => $contentData['id'] ?? null],
                                collect($contentData)->except(['created_at', 'updated_at'])->toArray()
                            );
                        }
                    }
                }
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

    public function saveCourseContent(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'uuid' => 'required|string',
                'sections' => 'array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $courseData = $request->data;
            $courseId = $request->uuid;



            // Update course
            $course = Course::where('uuid', $courseId)->firstOrFail();
            $realdata = collect($courseData)->except(['sections', 'id', 'created_at', 'updated_at'])->toArray();
           
            $course->update($request->data);
            
            
            return $realdata;

            if (isset($courseData['sections'])) {
                // Get existing section IDs for this course
                $existingSectionIds = Section::where('course_id', $courseId)->pluck('id')->toArray();
                $updatedSectionIds = [];

                foreach ($courseData['sections'] as $sectionData) {
                    $section = Section::updateOrCreate(
                        ['id' => $sectionData['id'] ?? null],
                        collect($sectionData)->except(['contents', 'created_at', 'updated_at'])->toArray()
                    );

                    $updatedSectionIds[] = $section->id;

                    if (isset($sectionData['contents'])) {
                        // Get existing content IDs for this section
                        $existingContentIds = Content::where('section_id', $section->id)->pluck('id')->toArray();
                        $updatedContentIds = [];

                        foreach ($sectionData['contents'] as $contentData) {
                            $content = Content::updateOrCreate(
                                ['id' => $contentData['id'] ?? null],
                                collect($contentData)->except(['created_at', 'updated_at'])->toArray()
                            );
                            $updatedContentIds[] = $content->id;
                        }

                        // Delete contents that are no longer in the updated data
                        Content::where('section_id', $section->id)
                            ->whereNotIn('id', $updatedContentIds)
                            ->delete();
                    }
                }

                // Delete sections that are no longer in the updated data
                Section::where('course_id', $courseId)
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
