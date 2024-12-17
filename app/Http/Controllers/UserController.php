<?php

namespace App\Http\Controllers;

use \Exception;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the all users.
     */
    public function index()
    {
        return UserResource::collection(User::all());
    }

    public function store2(Request $request)
    {
        dd("here");
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'phone' => ['required', 'string', 'max:50', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create a new user with validated data

            $data = $request->except(['file', 'image']);
            if ($request->has('image') && $request->image !== null) {
                $image = $request->image;
                $imageName = $image->hashName();
                $image->move(public_path("profilePic"), $imageName);
                $data['image'] = $imageName;
            }
            $data['uuid'] = Str::uuid();
            $data['password'] = Hash::make($data['password']);
            $user = User::create($data);

            // Return success response with user data
            return response()->json([
                'status' => true,
                'message' => 'User created successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur during user creation
            return response()->json([
                'status' => false,
                'message' => 'User creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display a single user
     */
    public function show()
    {
        try {
            $user = Auth::user();
            //Attempt to get the user details
            return response()->json([
                'status' => true,
                'message' => 'User Details Fetched successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur 
            return response()->json([
                'status' => false,
                'message' => 'User Details Not Fetched',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a single user.
     */
    public function update(Request $request)
    {
        try {

            $user = Auth::user();

            $data = $request->except(['file', 'image']);
            //Attempt to updte the user
            if ($request->has('image') && $request->image !== null) {
                // Check if there is an existing image and delete it
                $existingImage = $user->image; // Assuming $course is your model instance
                if ($existingImage && file_exists(public_path('profliePic/' . $existingImage))) {
                    unlink(public_path('profilePic/' . $existingImage));
                }

                // Upload the new image
                $image = $request->file('image');
                $imageName = $image->hashName();
                $image->move(public_path('profilePic'), $imageName);
                $data['image'] = $imageName;
            }
            $user = User::find($user->id);
            $user->update($data);
            return response()->json([
                'status' => true,
                'message' => 'User Updated successfully',
                'data' => $user,
            ], 200);
        } catch (Exception $e) {
            // Handle any errors that occur 
            return response()->json([
                'status' => false,
                'message' => 'User Not Updated',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a user
     */
    public function destroy(user $user)
    {
        try {
            $user->delete();
            return response()->json([
                'status' => true,
                'message' => 'User Deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur 
            return response()->json([
                'status' => false,
                'message' => 'User deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function change_role($id)
    {
        $user = User::where('uuid', $id)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 200);
        }
        if ($user->user_type == 'learners') {
            $user->user_type = 'trainers';
            $role = 'trainers';
        } else {
            $user->user_type = 'learners';
            $role = 'learners';
        }
        $user->save();
        return response()->json([
            'status' => true,
            'data' => $user,
            'message' => 'User status and roles changed to ' . $role,
        ], 200);
    }

    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            // 'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            // 'password' => 'required',
        ]);

        if ($validator->fails()) {
            // Return the validation errors
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user by email
        $realuser = DB::table('password_reset_tokens')->where('token', $request['token'])->latest()->first();
        $token = $realuser->token;
        $user = User::where('email', $realuser->email)->first();
        if ($token !== $request->token) {
            return response()->json(['message' => 'Invalid/Expired token']);
        }
        // Reset the user's password
        $user->password = Hash::make($request->password);
        $user->save();
        DB::table('password_reset_tokens')->where('token', $request['token'])->delete();
        return response()->json(['message' => 'Password reset successful'], 200);
    }

    public function forgot_password(Request $request)
    {

        try {

            // $this->validate($request, ['email' => 'required']);
            $data['confirm_id'] = $ref = Str::random(15);
            $email = $request->email;
            $check_email = User::where('email', $email)->first();
            if ($check_email == null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email Address Not Registered With Us.',
                ], 200);
            }
            $name = $check_email->first_name;


            DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => $ref,
            ]);



            //here is where the mail comes in
            $data = array('name' => $name, 'ref' => $ref, 'email' => $email);
            try {
                Mail::send('mail.forgot-password', $data, function ($message) use ($email) {
                    $message->to($email)->subject('CSLXP Reset Password');
                    $message->from('support@connectinskillz.com', 'Connectinskillz');
                });
                $data['message'] = 'Password Reset Mail Sent Successfully!';
            } catch (\Exception $e) {
                $data['message'] = 'Password reset mail could not be sent due to some technical issues!';
            }
            return response()->json([
                'status' => true,
                'message' => $data['message'],
                'token' => $ref
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
                'status' => false
            ], 500);
        }
    }
}
