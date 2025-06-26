<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DiscussionController extends Controller
{
    public function create(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'visibility' => 'required|in:public,private',
                'allowed_users' => 'nullable|array',
                'allowed_users.*' => 'exists:users,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'files' => 'nullable|file|max:5120',
                // 'course_id' => 'required|exists:courses,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $data = $request->except(['image', 'files']);
            $data['created_by'] = $user->id;
            $data['uuid'] = Str::uuid();

            if ($request->hasFile('image') && $request->image !== null) {
                $image = $request->file('image');
                $imageName = $image->hashName();
                $image->move(public_path('/discussionImages'), $imageName);
                $data['image'] = $imageName;
            }

            if ($request->hasFile('files') && $request->files !== null) {
                $file = $request->file('files');
                $fileName = $file->hashName();
                $file->move(public_path('/discussionFiles'), $fileName);
                $data['files'] = $fileName;
            }

            $discussion = Discussion::create($data);

            return response()->json([
                'status' => true,
                'data' => $discussion,
                'message' => 'Discussion Created Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $discussion = Discussion::findOrFail($id);

            if ($discussion->created_by !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'visibility' => 'sometimes|required|in:public,private',
                'allowed_users' => 'nullable|array',
                'allowed_users.*' => 'exists:users,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'files' => 'nullable|file|max:5120',
                'course_id' => 'sometimes|required|exists:courses,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $data = $request->except(['image', 'files']);

            if ($request->hasFile('image') && $request->image !== null) {
                if ($discussion->image && file_exists(public_path('/discussionImages/' . $discussion->image))) {
                    unlink(public_path('/discussionImages/' . $discussion->image));
                }
                $image = $request->file('image');
                $imageName = $image->hashName();
                $image->move(public_path('/discussionImages'), $imageName);
                $data['image'] = $imageName;
            }

            if ($request->hasFile('files') && $request->files !== null) {
                if ($discussion->files && file_exists(public_path('/discussionFiles/' . $discussion->files))) {
                    unlink(public_path('/discussionFiles/' . $discussion->files));
                }
                $file = $request->file('files');
                $fileName = $file->hashName();
                $file->move(public_path('/discussionFiles'), $fileName);
                $data['files'] = $fileName;
            }

            $discussion->update($data);

            return response()->json([
                'status' => true,
                'data' => $discussion,
                'message' => 'Discussion Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function delete($id)
    {
        try {
            $user = Auth::user();
            $discussion = Discussion::findOrFail($id);

            if ($discussion->created_by !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            if ($discussion->image && file_exists(public_path('/discussionImages/' . $discussion->image))) {
                unlink(public_path('/discussionImages/' . $discussion->image));
            }
            if ($discussion->files && file_exists(public_path('/discussionFiles/' . $discussion->files))) {
                unlink(public_path('/discussionFiles/' . $discussion->files));
            }

            $discussion->delete();

            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'Discussion Deleted Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function fetchByCourse($course_id)
    {
        try {
            $discussions = Discussion::where('course_id', $course_id)
                ->with(['createdBy', 'replies'])
                ->get();

            return response()->json([
                'status' => true,
                'data' => $discussions,
                'message' => 'Discussions fetched successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }


    public function createReply(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'discussion_id' => 'required|exists:discussions,id',
                'parent_reply_id' => 'nullable|exists:replies,id',
                'body' => 'required|string',
                'title' => 'nullable|string|max:255',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            // Check if user has access to the discussion
            $discussion = Discussion::findOrFail($request->discussion_id);
            if ($discussion->visibility === 'private' && !in_array($user->id, $discussion->allowed_users ?? []) && $discussion->created_by !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to reply to this discussion'
                ], 403);
            }

            $data = $request->except(['file']);
            $data['created_by'] = $user->id;
            $data['uuid'] = Str::uuid();

            if ($request->hasFile('file') && $request->file !== null) {
                $file = $request->file('file');
                $fileName = $file->hashName();
                $file->move(public_path('/replyFiles'), $fileName);
                $data['file'] = $fileName;
            }

            $reply = Reply::create($data);

            // Update discussion's reply_count
            $discussion->increment('reply_count');

            // Update parent reply's reply_count if applicable
            if ($request->parent_reply_id) {
                Reply::where('id', $request->parent_reply_id)->increment('reply_count');
            }

            return response()->json([
                'status' => true,
                'data' => $reply,
                'message' => 'Reply Created Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function updateReply(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $reply = Reply::findOrFail($id);

            if ($reply->created_by !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'body' => 'sometimes|required|string',
                'title' => 'nullable|string|max:255',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 401);
            }

            $data = $request->except(['file']);

            if ($request->hasFile('file') && $request->file !== null) {
                // Delete old file if exists
                if ($reply->file && file_exists(public_path('/replyFiles/' . $reply->file))) {
                    unlink(public_path('/replyFiles/' . $reply->file));
                }
                $file = $request->file('file');
                $fileName = $file->hashName();
                $file->move(public_path('/replyFiles'), $fileName);
                $data['file'] = $fileName;
            }

            $reply->update($data);

            return response()->json([
                'status' => true,
                'data' => $reply,
                'message' => 'Reply Updated Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function deleteReply($id)
    {
        try {
            $user = Auth::user();
            $reply = Reply::findOrFail($id);

            if ($reply->created_by !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Delete associated file if exists
            if ($reply->file && file_exists(public_path('/replyFiles/' . $reply->file))) {
                unlink(public_path('/replyFiles/' . $reply->file));
            }

            // Update discussion's reply_count
            $discussion = Discussion::findOrFail($reply->discussion_id);
            $discussion->decrement('reply_count');

            // Update parent reply's reply_count if applicable
            if ($reply->parent_reply_id) {
                Reply::where('id', $reply->parent_reply_id)->decrement('reply_count');
            }

            $reply->delete();

            return response()->json([
                'status' => true,
                'data' => null,
                'message' => 'Reply Deleted Successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

    public function fetchByDiscussion($discussion_id)
    {
        try {
            $user = Auth::user();
            $discussion = Discussion::findOrFail($discussion_id);

            // Check if user has access to the discussion
            if ($discussion->visibility === 'private' && !in_array($user->id, $discussion->allowed_users ?? []) && $discussion->created_by !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to view replies for this discussion'
                ], 403);
            }

            $replies = Reply::where('discussion_id', $discussion_id)
                ->with(['createdBy', 'parentReply', 'replies'])
                ->get();

            return response()->json([
                'status' => true,
                'data' => $replies,
                'message' => 'Replies fetched successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }

     public function fetchByUser($user_id)
    {
        try {
            $discussions = Discussion::where('created_by', $user_id)
                ->with(['createdBy', 'replies'])
                ->get();

            return response()->json([
                'status' => true,
                'data' => $discussions,
                'message' => 'Discussions fetched successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 401);
        }
    }
}
