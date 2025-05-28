<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Group;
use App\Models\GroupCourse;
use App\Models\GroupFile;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class GroupController extends Controller
{


    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'price' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 401);
        }
        try {
            $data = $request->all();
            $user = Auth::user();
            $data['user_id']  = $user->id;
            $check = Group::where('user_id', $user->id)->where('name', $request->name)->first();
            if ($check) {
                return response()->json([
                    'status' => false,
                    'message' => 'Group name already exist!'
                ], 200);
            }
            Group::create($data);
            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => 'Group Created Successfully!'
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
        $group = Group::find($request->id);
        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => 'Group not found'
            ], 404);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required',
            'description' => 'sometimes|required',
            'price' => 'sometimes|required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 401);
        }

        try {
            // Update the group's data
            $group->name = $request->name;
            $group->description = $request->description;
            $group->price = $request->price;
            $group->group_key = $request->group_key;
            $group->save();
            // $group->update($request->all());

            return response()->json([
                'status' => true,
                'data' => $group,
                'message' => 'Group Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
    public function view($id)
    {
        // Find the group by ID
        $group = Group::find($id);
        // Check if the group exists
        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => 'Group not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $group,
            'message' => 'Group retrieved successfully'
        ], 200);
    }

    public function allgroups()
    {
        // Retrieve all groups
        $user = Auth::user();
        $groups = Group::where('user_id', $user->id)->latest()->get();
        return response()->json([
            'status' => true,
            'data' => $groups,
            'message' => 'All groups retrieved successfully'
        ], 200);
    }


    public function delete($id)
    {
        // Find the group by ID
        $group = Group::find($id);
        if (!$group) {
            return response()->json([
                'status' => true,
                'message' => 'Group does not exist!'
            ], 404);
        }

        // Check if the group exists
        if (!$group) {
            return response()->json([
                'status' => false,
                'message' => 'Group not found'
            ], 404);
        }
        try {
            $user = Auth::user();
            if ($group->user_id != $user->id) {
                return response()->json([
                    'status' => true,
                    'message' => 'Permission denied to delete group!'
                ], 200);
            }
            $group->delete();

            return response()->json([
                'status' => true,
                'message' => 'Group Deleted Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function oldadd_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'group_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 401);
        }
        try {
            $data = $request->all();

            $check = GroupUser::where('user_id', $request->user_id)->where('group_id', $request->group_id)->first();
            if ($check) {
                return response()->json([
                    'status' => false,
                    'message' => "This user has been added to group already"
                ], 401);
            }
            $check_user = User::find($request->user_id);
            if (!$check_user) {
                return response()->json([
                    'status' => false,
                    'message' => "Id of user does not exist!"
                ], 401);
            }
            $group_user = GroupUser::create($data);

            return response()->json([
                'status' => true,
                'data' => $group_user,
                'message' => 'User added to group Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }


    public function add_user(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required',

            'group_id' => 'required|integer',
        ]);

        $user_ids = $request->user_ids;
        if (is_string($user_ids)) {
            $user_ids = array_map('intval', array_filter(explode(',', $user_ids)));
        } elseif (!is_array($user_ids)) {
            return response()->json([
                'status' => false,
                'message' => 'user_ids must be an array or comma-separated string'
            ], 401);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 401);
        }

        try {
            $group_id = $request->group_id;
            $added_users = [];
            $failed_users = [];

            foreach ($user_ids as $user_id) {
                // Check if user exists
                $check_user = User::find($user_id);
                if (!$check_user) {
                    $failed_users[] = [
                        'user_id' => $user_id,
                        'message' => 'User does not exist'
                    ];
                    continue;
                }

                // Check if user is already in group
                $check = GroupUser::where('user_id', $user_id)
                    ->where('group_id', $group_id)
                    ->first();
                if ($check) {
                    $failed_users[] = [
                        'user_id' => $user_id,
                        'message' => 'User already in group'
                    ];
                    continue;
                }

                // Add user to group
                $group_user = GroupUser::create([
                    'user_id' => $user_id,
                    'group_id' => $group_id
                ]);

                $added_users[] = $group_user;
            }

            $response = [
                'status' => true,
                'message' => 'Users processed successfully',
                'data' => [
                    'added_users' => $added_users,
                    'failed_users' => $failed_users
                ]
            ];

            // If no users were added successfully, return error
            if (empty($added_users) && !empty($failed_users)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No users were added',
                    'data' => [
                        'added_users' => [],
                        'failed_users' => $failed_users
                    ]
                ], 401);
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'added_users' => [],
                    'failed_users' => []
                ]
            ], 401);
        }
    }

    public function newadd_user(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required_without:course_id',
            'group_id' => 'required',
            // 'course_id' => 'required_without:user_id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 401);
        }
        try {
            if ($request->has('course_id')) {
                // Get the course
                $course = Course::find($request->course_id);
                if (!$course) {
                    return response()->json([
                        'status' => false,
                        'message' => "Course does not exist!"
                    ], 401);
                }

                // Get all users who have purchased/enrolled in the course
                // Since there's no direct enrollment table, we'll use the course's user_id
                // This is a simplified approach - you may need to adjust based on your actual enrollment tracking
                $enrolled_users = User::where('id', $course->user_id)->get();

                $added_users = 0;
                foreach ($enrolled_users as $user) {
                    // Check if user is already in the group
                    $check = GroupUser::where('user_id', $user->id)
                        ->where('group_id', $request->group_id)
                        ->first();

                    if (!$check) {
                        GroupUser::create([
                            'user_id' => $user->id,
                            'group_id' => $request->group_id
                        ]);
                        $added_users++;
                    }
                }

                return response()->json([
                    'status' => true,
                    'message' => $added_users . ' users from course added to group Successfully!'
                ], 200);
            } else {
                // Original single user add logic
                $data = $request->all();
                $check = GroupUser::where('user_id', $request->user_id)
                    ->where('group_id', $request->group_id)
                    ->first();

                if ($check) {
                    return response()->json([
                        'status' => false,
                        'message' => "This user has been added to group already"
                    ], 401);
                }

                $check_user = User::find($request->user_id);
                if (!$check_user) {
                    return response()->json([
                        'status' => false,
                        'message' => "Id of user does not exist!"
                    ], 401);
                }

                $group_user = GroupUser::create($data);

                return response()->json([
                    'status' => true,
                    'data' => $group_user,
                    'message' => 'User added to group Successfully!'
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function remove_user($group_id, $user_id)
    {

        try {

            // Find the GroupUser record for the specified user and group
            $group_user = GroupUser::where('user_id', $user_id)
                ->where('group_id', $group_id)
                ->first();

            if (!$group_user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found in the group',
                ], 404);
            }

            // Delete the user from the group
            $group_user->delete();

            return response()->json([
                'status' => true,
                'message' => 'User removed from group successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500); // Use status 500 for server errors
        }
    }

    public function add_course(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'course_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 401);
        }
        try {
            $data = $request->all();
            $check = GroupCourse::where('course_id', $request->course_id)->where('group_id', $request->group_id)->first();
            if ($check) {
                return response()->json([
                    'status' => false,
                    'message' => "This course has been added to group already"
                ], 401);
            }
            $group_user = GroupCourse::create($data);

            return response()->json([
                'status' => true,
                'data' => $group_user,
                'message' => 'Coursed added to group Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function remove_course($group_id, $course_id)
    {


        try {

            $group_course = GroupCourse::where('group_id', $group_id)
                ->where('course_id', $course_id)
                ->first();

            if (!$group_course) {
                return response()->json([
                    'status' => false,
                    'message' => 'Course not found in the group',
                ], 404);
            }

            // Delete the course from the group
            $group_course->delete();

            return response()->json([
                'status' => true,
                'message' => 'Course removed from group successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500); // Use status 500 for server errors
        }
    }

    public function users($group_id)
    {

        try {

            $data['user_id'] = Auth::user()->id;
            $groups = Group::with('groupusers.users')
                ->withCount('groupusers')->find($group_id);

            return response()->json([
                'status' => true,
                'data' => $groups,
                'message' => 'Groups with users fetched Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
    public function courses($group_id)
    {

        try {
            $data['user_id'] = Auth::user()->id;
            $groups = Group::with('groupcourses.courses')
                ->withCount('groupcourses')->find($group_id);

            return response()->json([
                'status' => true,
                'data' => $groups,
                'message' => 'Group with courses fetched Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function oldadd_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,docx,txt|max:2048', // Adjust types if needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            // Check group ownership
            $group = Group::where('id', $request->group_id)
                ->where('user_id', $user->id)
                ->first();
            // var_dump($group);

            if (!$group) {
                return response()->json([
                    'status' => false,
                    'message' => 'Group not found or access denied.'
                ], 403);
            }

            $data = $request->except(['file']);
            $data['user_id'] = $user->id;
            $data['group_id'] = $group->id;
            $data['uuid'] = Str::uuid(); // Optional unique ID

            // Handle the file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = $file->hashName(); // Generates unique name
                $file->move(public_path('/groupFiles'), $fileName);
                $data['filename'] = $fileName;
                $data['filepath'] = 'groupFiles/' . $fileName; // Relative path
            }

            // Save file record to database
            $fileRecord = GroupFile::create($data);

            return response()->json([
                'status' => true,
                'data' => $fileRecord,
                'message' => 'File uploaded successfully!'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }





    public function add_file(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf,docx,txt|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            // Check group ownership
            $group = Group::where('id', $request->group_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => false,
                    'message' => 'Group not found or access denied.'
                ], 403);
            }

            $data = $request->except(['file']);
            $data['user_id'] = $user->id;
            $data['group_id'] = $group->id;
            $data['uuid'] = Str::uuid();

            // Handle the file upload
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                // Get file size and type before moving
                $fileSize = $file->getSize(); // File size in bytes
                $fileType = $file->getClientMimeType(); // File MIME type
                $originalFileName = $file->getClientOriginalName(); // Get original filename
                // Create unique filename with UUID
                $fileName = pathinfo($originalFileName, PATHINFO_FILENAME) . '_' . Str::uuid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('/groupFiles'), $fileName);
                $data['name'] = $originalFileName; // Store original filename
                $data['filename'] = $fileName; // Store unique filename
                $data['filepath'] = 'groupFiles/' . $fileName; // Store filepath
                $data['file_size'] = $fileSize;
                $data['file_type'] = $fileType;
            }

            // Save file record to database
            $fileRecord = GroupFile::create($data);

            return response()->json([
                'status' => true,
                'data' => [
                    'file_record' => $fileRecord,
                    'file_size' => $data['file_size'],
                    'file_type' => $data['file_type'],
                    'original_name' => $data['name']
                ],
                'message' => 'File uploaded successfully!'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }



    public function remove_file($group_id, $file_id)
    {
        try {
            $user = Auth::user();

            // Find the file with matching group, user, and file ID
            $file = GroupFile::where('id', $file_id)
                ->where('group_id', $group_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$file) {
                return response()->json([
                    'status' => false,
                    'message' => 'File not found or access denied.'
                ], 404);
            }

            // Delete the physical file if it exists
            $filePath = public_path($file->filepath);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete the database record
            $file->delete();

            return response()->json([
                'status' => true,
                'message' => 'File deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function files($group_id)
    {
        try {
            $user = Auth::user();

            // Ensure the group belongs to the authenticated user
            $group = Group::where('id', $group_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$group) {
                return response()->json([
                    'status' => false,
                    'message' => 'Group not found or access denied.'
                ], 404);
            }

            $files = GroupFile::where('group_id', $group_id)->get();

            return response()->json([
                'status' => true,
                'data' => $files,
                'message' => 'Files retrieved successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }
}
