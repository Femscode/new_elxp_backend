<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupCourse;
use App\Models\GroupUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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

    public function add_user(Request $request)
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
            if(!$check_user) {
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
            $groups = Group::with('groupusers')
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
}
