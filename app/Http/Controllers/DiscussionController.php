<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
                'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:5120',
                'video' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:25600',
                'files' => 'nullable|file|max:25600',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $data = $request->except(['image', 'files', 'video']);
            $data['created_by'] = $user->id;
            $data['uuid'] = Str::uuid();

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = $image->hashName();
                $image->move(public_path('/discussionImages'), $imageName);
                $data['image'] = $imageName;
            }

            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $videoName = $video->hashName();
                $video->move(public_path('/discussionVideos'), $videoName);
                $data['video'] = $videoName;
            }

            if ($request->hasFile('files')) {
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
                'body' => 'required_without:content|string',
                'content' => 'required_without:body|string',
                'title' => 'nullable|string|max:255',
                'file' => 'nullable|file|max:10240',
                'image' => 'nullable|file|mimes:jpeg,png,jpg,gif|max:5120',
                'video' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:25600',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            // Check access
            $discussion = Discussion::findOrFail($request->discussion_id);
            if ($discussion->visibility === 'private' && !in_array($user->id, $discussion->allowed_users ?? []) && $discussion->created_by !== $user->id) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $data = $request->except(['file', 'image', 'video']);
            $data['created_by'] = $user->id;
            $data['uuid'] = Str::uuid();
            
            // Cross compatibility for content/body
            if (!$request->has('body') && $request->has('content')) {
                $data['body'] = $request->content;
            }

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $name = $file->hashName();
                $file->move(public_path('/replyFiles'), $name);
                $data['file'] = $name;
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $name = $file->hashName();
                $file->move(public_path('/replyImages'), $name);
                $data['image'] = $name;
            }

            if ($request->hasFile('video')) {
                $file = $request->file('video');
                $name = $file->hashName();
                $file->move(public_path('/replyVideos'), $name);
                $data['video'] = $name;
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
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($r) use ($user) {
                    $r->is_liked = DB::table('reply_likes')
                        ->where('reply_id', $r->id)
                        ->where('user_id', $user->id)
                        ->exists();
                    return $r;
                });

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

    public function fetchAll(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->query('type', 'all'); 
            $search = $request->query('search');

            $query = Discussion::with(['createdBy'])
                ->withCount(['replies', 'likedByUsers']);

            // Core filtering
            if ($type === 'trending') {
                // Trending criteria: >20 likes OR >15 replies
                $query->where(function($q) {
                    $q->where('like_count', '>', 20)
                      ->orWhere('reply_count', '>', 15);
                })->orderBy('like_count', 'desc');
            } elseif ($type === 'my') {
                $query->where('created_by', $user->id);
            } elseif ($type === 'saved') {
                $query->whereHas('savedByUsers', function($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Global search
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('content', 'LIKE', "%{$search}%");
                });
            }

            // Secondary fallback ordering
            if ($type !== 'trending') {
                $query->orderBy('created_at', 'desc');
            }

            // Paginating
            $discussions = $query->paginate(10);

            // Dynamically attach states for user visibility (liked/saved)
            $discussions->getCollection()->transform(function($item) use ($user) {
                // Convert DB models to clean formats suitable for standard JS frontend consuming
                $item->is_liked = DB::table('discussion_likes')->where('discussion_id', $item->id)->where('user_id', $user->id)->exists();
                $item->is_saved = DB::table('discussion_saves')->where('discussion_id', $item->id)->where('user_id', $user->id)->exists();
                return $item;
            });

            return response()->json([
                'status' => true,
                'data' => $discussions,
                'message' => 'Discussions fetched successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function fetchSingle($id)
    {
        try {
            $user = Auth::user();
            $discussion = Discussion::with(['createdBy'])->findOrFail($id);

            $discussion->is_liked = DB::table('discussion_likes')->where('discussion_id', $discussion->id)->where('user_id', $user->id)->exists();
            $discussion->is_saved = DB::table('discussion_saves')->where('discussion_id', $discussion->id)->where('user_id', $user->id)->exists();

            return response()->json([
                'status' => true,
                'data' => $discussion,
                'message' => 'Discussion fetched successfully!'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 404);
        }
    }

    public function toggleLike($id)
    {
        try {
            $user = Auth::user();
            $discussion = Discussion::findOrFail($id);

            $existing = DB::table('discussion_likes')
                ->where('discussion_id', $discussion->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                DB::table('discussion_likes')->where('id', $existing->id)->delete();
                $discussion->decrement('like_count');
                $action = 'unliked';
            } else {
                DB::table('discussion_likes')->insert([
                    'discussion_id' => $discussion->id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $discussion->increment('like_count');
                $action = 'liked';
            }

            return response()->json([
                'status' => true,
                'action' => $action,
                'like_count' => $discussion->refresh()->like_count,
                'message' => 'Reaction updated'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleSave($id)
    {
        try {
            $user = Auth::user();
            $discussion = Discussion::findOrFail($id);

            $existing = DB::table('discussion_saves')
                ->where('discussion_id', $discussion->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                DB::table('discussion_saves')->where('id', $existing->id)->delete();
                $action = 'unsaved';
            } else {
                DB::table('discussion_saves')->insert([
                    'discussion_id' => $discussion->id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $action = 'saved';
            }

            return response()->json([
                'status' => true,
                'action' => $action,
                'message' => 'Save status updated'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleReplyLike($id)
    {
        try {
            $user = Auth::user();
            $reply = Reply::findOrFail($id);

            $existing = DB::table('reply_likes')
                ->where('reply_id', $reply->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                DB::table('reply_likes')->where('id', $existing->id)->delete();
                $reply->decrement('like_count');
                $action = 'unliked';
            } else {
                DB::table('reply_likes')->insert([
                    'reply_id' => $reply->id,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $reply->increment('like_count');
                $action = 'liked';
            }

            return response()->json([
                'status' => true,
                'action' => $action,
                'like_count' => $reply->refresh()->like_count,
                'message' => 'Reaction recorded'
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
