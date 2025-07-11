<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Calender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


class CalenderController extends Controller
{
     
    // List all calendar entries for authenticated user
    public function index()
    {
        $user = Auth::user();
        $calender = Calender::where('user_id', $user->id)->get();
        $calender['organiser'] = $user->first_name . ' ' . $user->last_name;

        return response()->json([
            'status' => true,
            'data' => $calender
        ], 200);
    }

    public function create(Request $request)
    {
            try {
                $user = Auth::user();

                    $validator = Validator::make($request->all(), [
                    'name' => 'required|string|max:255',
                    'date' => 'required|date',
                    'time' => 'required',
                    'duration'=>'required',
                    'unit' => 'required|in:minutes,hours',
                    'audience' => 'required|in:private,specific,public',
                    'color' => 'nullable|string|max:50',
                    'description' => 'required',
                    'status' => 'nullable|boolean',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => $validator
                ], 401);
                }

                $data = $request->all();
                $data['user_id'] = $user->id;
                $data['uuid'] = Str::uuid();
                $data['status'] = $request->input('status', 0);

                

                $calender = Calender::create($data);

                return response()->json([
                    'status' => true,
                    'message' => 'Event created successfully!',
                    'data' => $calender
                ], 201);

            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
        }
    }

     // Show a single calendar entry
    public function fetchByEvent($id)
    {
        $user = Auth::user();
        $calender = Calender::where('user_id', $user->id)->find($id);
        $calender['organiser'] = $user->first_name . ' ' . $user->last_name;

        if (!$calender) {
            return response()->json([
                'status' => false,
                'message' => 'Event not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $calender
        ], 200);
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $calender = Calender::where('user_id', $user->id)->find($id);
            $calender['organiser'] = $user->first_name . ' ' . $user->last_name;

            if (!$calender) {
                return response()->json([
                    'status' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'date' => 'sometimes|required|date',
                'time' => 'sometimes|required',
                'duration'=>'sometimes|required',
                'unit' => 'sometimes|required|in:minutes,hours',
                'audience' =>'sometimes|required|in:private,specific,public',
                'color' => 'nullable|string|max:50',
                'color'=> 'sometimes | required',
                'status' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator
                ], 401);
            }

            $calender->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Event updated successfully!',
                'data' => $calender
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function delete($id)
    {
        $user = Auth::user();
        $calender = Calender::where('user_id', $user->id)->find($id);

        if (!$calender) {
            return response()->json([
                'status' => false,
                'message' => 'Event not found'
            ], 404);
        }

        $calender->delete();

        return response()->json([
            'status' => true,
            'message' => 'Event deleted successfully!'
        ], 200);
    }

    public function count()
    {
        $user = Auth::user();

        $total = Calender::where('user_id', $user->id)->count();

        return response()->json([
            'status' => true,
            'total_events' => $total
        ], 200);
    }
    
}