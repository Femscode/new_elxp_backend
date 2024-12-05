<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'bill_name' => $this->bill_name,
            'description' => $this->description,
            'amount' => floatval($this->amount),
            'balance_before_payment' => floatval($this->before_balance),
            'balance_after_payment' => floatval($this->after_balance),
            'transaction_date' => $this->transaction_date,
        ];
    }
}
