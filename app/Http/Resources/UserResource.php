<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'referred_by' => $this->referred_by,
            'referral_id' => $this->referral_id,
            'bio' => $this->bio,
            'image' => $this->image ? "https://elxp-backend.connectinskillz.com/elxp_files/public/profilePic/" . $this->image : null,
            'language' => $this->language,
            'phone' => $this->phone,
            'email' => $this->email,
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
