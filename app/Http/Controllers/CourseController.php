<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
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
