<?php

namespace App\Http\Controllers;

use App\Models\MBIContactUs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MBIController extends Controller
{
    public function saveContact(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:255',
                'subject' => 'nullable|string',
                'message' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()
                ], 400);
            }

            $validated = $validator->validated();

            // Create contact us record
            $contact = MBIContactUs::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'subject' => $validated['subject'] ?? null,
                'message' => $validated['message'] ?? null,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Contact form submitted successfully!',
                'data' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'subject' => $contact->subject,
                    'message' => $contact->message,
                    'createdAt' => $contact->created_at->toISOString(),
                    'updatedAt' => $contact->updated_at->toISOString(),
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchContact($id)
    {
        try {
            $contact = MBIContactUs::find($id);
            if (!$contact) {
                return response()->json([
                    'status' => false,
                    'message' => 'Contact not found!',
                ], 404);
            }

            

            return response()->json([
                'status' => true,
                'message' => 'Contact record retrieved successfully!',
                'data' => $contact
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchAllContact(Request $request)
    {
        try {
            $contacts = MBIContactUs::orderBy('created_at', 'desc')->get();

            $formattedContacts = $contacts->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'subject' => $contact->subject,
                    'message' => $contact->message,
                    'createdAt' => $contact->created_at->toISOString(),
                    'updatedAt' => $contact->updated_at->toISOString(),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Contact records retrieved successfully!',
                'data' => $formattedContacts
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
