<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Content;
use App\Models\ContentCompletion;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminCourseController extends Controller
{
    /**
     * List all courses with trainer, enrollment count, and section count.
     */
    public function index(Request $request)
    {
        try {
            $courses = Course::with('instructor')
                ->orderBy('created_at', 'desc')
                ->get();

            $formatted = $courses->map(function ($course) {
                $enrollmentCount = Enrollment::where('course_id', $course->uuid)->count();
                $sectionCount    = Section::where('course_id', $course->uuid)->count();
                $contentCount    = Content::where('course_id', $course->uuid)->count();

                $trainerName = 'Unknown Trainer';
                $trainerInitial = '?';
                if ($course->instructor) {
                    $trainerName    = trim($course->instructor->first_name . ' ' . $course->instructor->last_name);
                    $trainerInitial = strtoupper(substr($course->instructor->first_name ?? 'U', 0, 1));
                }

                return [
                    'id'                  => $course->uuid,
                    'uuid'                => $course->uuid,
                    'title'               => $course->title,
                    'description'         => $course->description,
                    'price'               => $course->price,
                    'image'               => $course->image,
                    'course_code'         => $course->course_code,
                    'status'              => $course->status ?? 'published',
                    'certificate_template'=> $course->certificate_template ?? 1,
                    'trainerName'         => $trainerName,
                    'trainerInitial'      => $trainerInitial,
                    'trainerUuid'         => $course->user_id,
                    'enrollmentCount'     => $enrollmentCount,
                    'sectionCount'        => $sectionCount,
                    'contentCount'        => $contentCount,
                    'createdAt'           => $course->created_at,
                ];
            });

            // Platform-level aggregate stats
            $totalEnrollments = Enrollment::count();
            $totalCourses     = $courses->count();

            return response()->json([
                'status'  => true,
                'message' => 'Courses fetched successfully',
                'data'    => [
                    'courses'          => $formatted,
                    'totalEnrollments' => $totalEnrollments,
                    'totalCourses'     => $totalCourses,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch courses',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get full course details: sections + contents tree (for the admin content viewer).
     */
    public function show($uuid)
    {
        try {
            $course = Course::with([
                'instructor',
                'sections' => function ($q) {
                    $q->orderBy('position')->with(['contents' => function ($cq) {
                        $cq->orderBy('position');
                    }]);
                },
            ])->where('uuid', $uuid)->firstOrFail();

            $enrollmentCount = Enrollment::where('course_id', $uuid)->count();
            $contentCount    = Content::where('course_id', $uuid)->count();

            $trainerName = 'Unknown Trainer';
            if ($course->instructor) {
                $trainerName = trim($course->instructor->first_name . ' ' . $course->instructor->last_name);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Course details fetched',
                'data'    => [
                    'uuid'                 => $course->uuid,
                    'title'                => $course->title,
                    'description'          => $course->description,
                    'price'                => $course->price,
                    'image'                => $course->image,
                    'course_code'          => $course->course_code,
                    'status'               => $course->status ?? 'published',
                    'certificate_template' => $course->certificate_template ?? 1,
                    'trainerName'          => $trainerName,
                    'trainerUuid'          => $course->user_id,
                    'enrollmentCount'      => $enrollmentCount,
                    'contentCount'         => $contentCount,
                    'sections'             => $course->sections->map(function ($section) {
                        return [
                            'id'       => $section->id,
                            'name'     => $section->name,
                            'position' => $section->position,
                            'contents' => $section->contents->map(function ($content) {
                                return [
                                    'id'          => $content->id,
                                    'title'       => $content->title,
                                    'contentType' => $content->contentType,
                                    'data'        => $content->data,
                                    'file'        => $content->file,
                                    'position'    => $content->position,
                                ];
                            }),
                        ];
                    }),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Course not found',
                'error'   => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update course meta (title, description, price, status, certificate_template).
     */
    public function update(Request $request, $uuid)
    {
        try {
            $course = Course::where('uuid', $uuid)->first();
            if (!$course) {
                return response()->json(['status' => false, 'message' => 'Course not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'title'                => ['sometimes', 'required', 'string', 'min:3'],
                'description'          => ['nullable', 'string'],
                'price'                => ['sometimes', 'required', 'numeric', 'min:0'],
                'status'               => ['nullable', 'string', Rule::in(['published', 'draft'])],
                'certificate_template' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation Error',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            $course->update(array_filter([
                'title'                => $request->title,
                'description'          => $request->description,
                'price'                => $request->price,
                'status'               => $request->status,
                'certificate_template' => $request->certificate_template,
            ], fn($v) => !is_null($v)));

            return response()->json([
                'status'  => true,
                'message' => 'Course updated successfully',
                'data'    => $course->refresh(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update course',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Permanently delete a course and all its related data.
     */
    public function destroy($uuid)
    {
        try {
            $course = Course::where('uuid', $uuid)->first();
            if (!$course) {
                return response()->json(['status' => false, 'message' => 'Course not found'], 404);
            }

            DB::beginTransaction();

            // Count related data for reporting
            $sectionCount    = Section::where('course_id', $uuid)->count();
            $contentCount    = Content::where('course_id', $uuid)->count();
            $enrollmentCount = Enrollment::where('course_id', $uuid)->count();

            // Cascade delete: completions, enrollments, contents, sections
            ContentCompletion::where('course_id', $uuid)->delete();
            Enrollment::where('course_id', $uuid)->delete();
            Content::where('course_id', $uuid)->delete();
            Section::where('course_id', $uuid)->delete();

            $course->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Course deleted successfully',
                'deleted' => [
                    'sections'    => $sectionCount,
                    'contents'    => $contentCount,
                    'enrollments' => $enrollmentCount,
                ],
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete course',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all enrollments for a specific course with per-learner progress.
     */
    public function enrollments($uuid)
    {
        try {
            $course = Course::where('uuid', $uuid)->first();
            if (!$course) {
                return response()->json(['status' => false, 'message' => 'Course not found'], 404);
            }

            $totalContents = Content::where('course_id', $uuid)->count();
            $enrollments   = Enrollment::with('user')
                ->where('course_id', $uuid)
                ->orderBy('created_at', 'desc')
                ->get();

            $formatted = $enrollments->map(function ($enrollment) use ($uuid, $totalContents) {
                $completedContents = ContentCompletion::where('course_id', $uuid)
                    ->where('user_id', $enrollment->user_id)
                    ->count();

                $progress = $totalContents > 0
                    ? min(round(($completedContents / $totalContents) * 100, 1), 100)
                    : 0;

                $learnerName = 'Unknown Learner';
                $email       = '';
                if ($enrollment->user) {
                    $learnerName = trim($enrollment->user->first_name . ' ' . $enrollment->user->last_name);
                    $email       = $enrollment->user->email;
                }

                return [
                    'id'               => $enrollment->id,
                    'learnerName'      => $learnerName,
                    'email'            => $email,
                    'status'           => $enrollment->status ?? 'active',
                    'amountPaid'       => $enrollment->amount_paid ?? 0,
                    'progress'         => $progress,
                    'completedContents'=> $completedContents,
                    'totalContents'    => $totalContents,
                    'enrolledAt'       => $enrollment->created_at,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Enrollments fetched',
                'data'    => [
                    'enrollments'    => $formatted,
                    'totalEnrolled'  => $enrollments->count(),
                    'avgProgress'    => $formatted->count() > 0
                        ? round($formatted->avg('progress'), 1)
                        : 0,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to fetch enrollments',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
