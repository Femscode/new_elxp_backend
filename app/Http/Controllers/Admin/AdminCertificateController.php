<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCertificateController extends Controller
{
    /**
     * Display a listing of certificates, plus learners and courses resources.
     */
    public function index(Request $request)
    {
        try {
            $certificates = Certificate::with(['user', 'course.instructor'])
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedCertificates = $certificates->map(function ($cert) {
                $instructorName = 'ELXP Mentor';
                $templateId = 1;
                $courseTitle = 'Unknown Course';

                if ($cert->course) {
                    $courseTitle = $cert->course->title;
                    $templateId = $cert->course->certificate_template ?: 1;
                    if ($cert->course->instructor) {
                        $instructorName = trim($cert->course->instructor->first_name . ' ' . $cert->course->instructor->last_name);
                    }
                }

                return [
                    'id' => $cert->uuid,
                    'uuid' => $cert->uuid,
                    'user_id' => $cert->user_id,
                    'course_id' => $cert->course_id,
                    'learnerName' => $cert->user ? trim($cert->user->first_name . ' ' . $cert->user->last_name) : 'Unknown Student',
                    'course' => $courseTitle,
                    'issuedDate' => $cert->issued_date,
                    'expiryDate' => $cert->expiry_date,
                    'verificationCode' => $cert->verification_code,
                    'status' => $cert->status ?: 'Active',
                    'instructor' => $instructorName,
                    'template' => $templateId,
                ];
            });

            // Fetch learners and courses to populate dropdown select inputs
            $learners = User::whereRaw('LOWER(user_type) LIKE ?', ['learner%'])
                ->orderBy('first_name')
                ->get();

            $courses = Course::with('instructor')->orderBy('title')->get();

            return response()->json([
                'status' => true,
                'message' => 'Certificates data fetched successfully',
                'data' => [
                    'certificates' => $formattedCertificates,
                    'learners' => $learners->map(function ($u) {
                        return [
                            'id' => $u->uuid,
                            'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                            'email' => $u->email,
                        ];
                    }),
                    'courses' => $courses->map(function ($c) {
                        return [
                            'id' => $c->uuid,
                            'title' => $c->title,
                            'instructor' => $c->instructor ? trim($c->instructor->first_name . ' ' . $c->instructor->last_name) : 'ELXP Mentor',
                            'template' => $c->certificate_template ?: 1,
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch certificates data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created certificate.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => ['required', 'string', 'exists:users,uuid'],
                'course_id' => ['required', 'string', 'exists:courses,uuid'],
                'issued_date' => ['required', 'date'],
                'expiry_date' => ['nullable', 'date'],
                'status' => ['nullable', 'string', Rule::in(['Active', 'Expired'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate deterministic or unique verification code
            $year = date('Y', strtotime($request->issued_date));
            $randomCode = strtoupper(Str::random(6));
            $verificationCode = "CERT-{$year}-{$randomCode}";

            // Ensure unique verification code
            while (Certificate::where('verification_code', $verificationCode)->exists()) {
                $randomCode = strtoupper(Str::random(6));
                $verificationCode = "CERT-{$year}-{$randomCode}";
            }

            $cert = Certificate::create([
                'uuid' => (string) Str::uuid(),
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
                'issued_date' => $request->issued_date,
                'expiry_date' => $request->expiry_date,
                'verification_code' => $verificationCode,
                'status' => $request->status ?: 'Active',
            ]);

            // Load relations to return formatted response
            $cert->load(['user', 'course.instructor']);
            
            $instructorName = 'ELXP Mentor';
            $templateId = 1;
            if ($cert->course) {
                $templateId = $cert->course->certificate_template ?: 1;
                if ($cert->course->instructor) {
                    $instructorName = trim($cert->course->instructor->first_name . ' ' . $cert->course->instructor->last_name);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Certificate issued successfully',
                'data' => [
                    'id' => $cert->uuid,
                    'uuid' => $cert->uuid,
                    'user_id' => $cert->user_id,
                    'course_id' => $cert->course_id,
                    'learnerName' => $cert->user ? trim($cert->user->first_name . ' ' . $cert->user->last_name) : 'Unknown Student',
                    'course' => $cert->course ? $cert->course->title : 'Unknown Course',
                    'issuedDate' => $cert->issued_date,
                    'expiryDate' => $cert->expiry_date,
                    'verificationCode' => $cert->verification_code,
                    'status' => $cert->status,
                    'instructor' => $instructorName,
                    'template' => $templateId,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to issue certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified certificate.
     */
    public function update(Request $request, $uuid)
    {
        try {
            $cert = Certificate::where('uuid', $uuid)->first();
            if (!$cert) {
                return response()->json([
                    'status' => false,
                    'message' => 'Certificate not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => ['required', 'string', 'exists:users,uuid'],
                'course_id' => ['required', 'string', 'exists:courses,uuid'],
                'issued_date' => ['required', 'date'],
                'expiry_date' => ['nullable', 'date'],
                'status' => ['nullable', 'string', Rule::in(['Active', 'Expired'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cert->update([
                'user_id' => $request->user_id,
                'course_id' => $request->course_id,
                'issued_date' => $request->issued_date,
                'expiry_date' => $request->expiry_date,
                'status' => $request->status ?: $cert->status,
            ]);

            $cert->load(['user', 'course.instructor']);

            $instructorName = 'ELXP Mentor';
            $templateId = 1;
            if ($cert->course) {
                $templateId = $cert->course->certificate_template ?: 1;
                if ($cert->course->instructor) {
                    $instructorName = trim($cert->course->instructor->first_name . ' ' . $cert->course->instructor->last_name);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Certificate updated successfully',
                'data' => [
                    'id' => $cert->uuid,
                    'uuid' => $cert->uuid,
                    'user_id' => $cert->user_id,
                    'course_id' => $cert->course_id,
                    'learnerName' => $cert->user ? trim($cert->user->first_name . ' ' . $cert->user->last_name) : 'Unknown Student',
                    'course' => $cert->course ? $cert->course->title : 'Unknown Course',
                    'issuedDate' => $cert->issued_date,
                    'expiryDate' => $cert->expiry_date,
                    'verificationCode' => $cert->verification_code,
                    'status' => $cert->status,
                    'instructor' => $instructorName,
                    'template' => $templateId,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified certificate.
     */
    public function destroy($uuid)
    {
        try {
            $cert = Certificate::where('uuid', $uuid)->first();
            if (!$cert) {
                return response()->json([
                    'status' => false,
                    'message' => 'Certificate not found'
                ], 404);
            }

            $cert->delete();

            return response()->json([
                'status' => true,
                'message' => 'Certificate deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete certificate',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
