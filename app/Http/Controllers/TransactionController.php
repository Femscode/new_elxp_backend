<?php

namespace App\Http\Controllers;

use App\Http\Controllers\UserController;
use App\Http\Resources\TransactionResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */

  

    public function index()
    {
        return TransactionResource::collection(Transaction::all());
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'max:500000'],

        ]);
        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }


        try {
            $user = Auth::user();
            $data = $request->all();

            //adding other neccesary fields before creation
            $data['user_id'] = $user->uuid;
            $data['uuid'] = Str::uuid();
            $data['before_balance'] = $user->balance;
            $data['after_balance'] = $user->balance - $data['amount'];
            $data['transaction_date'] = Carbon::now();

            $transaction = Transaction::create($data);
            //Update user balance
            $user->balance -= $data['amount'];
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Transaction Made successfully',
                'data' => new TransactionResource($transaction),
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur
            return response()->json([
                'status' => false,
                'message' => 'Bill Payment Failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a single transaction.
     */
    public function show(Transaction $transaction)
    {
        // return new TransactionResource($transaction);

        try {
            //Attempt to get the user details
            return response()->json([
                'status' => true,
                'message' => 'Transaction Info Fetched successfully',
                'data' => new TransactionResource($transaction),
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur 
            return response()->json([
                'status' => false,
                'message' => 'Transaction Info Not Fetched',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update single transaction.
     */
    public function update(Request $request, Transaction $transaction)
    {
       
        try {

            //Attempt to updte the user
            $transaction->update($request->all());
            return response()->json([
                'status' => true,
                'message' => 'Transaction Updated successfully',
                'data' => new TransactionResource($transaction),
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur 
            return response()->json([
                'status' => false,
                'message' => 'Transaction Not Updated',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a single transaction.
     */
    public function destroy(Transaction $transaction)
    {
       

        try {
            $transaction->delete();
            return response()->json([
                'status' => true,
                'message' => 'Transaction Deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            // Handle any errors that occur 
            return response()->json([
                'status' => false,
                'message' => 'Transaction deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
