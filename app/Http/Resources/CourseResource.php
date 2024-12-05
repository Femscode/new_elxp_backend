<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'course_code' => $this->course_code,
            'instructor_id' => $this->instructor_id,
            'category_id' => $this->category_id,
            'status' => $this->status,
           'image' => $this->image ? "https://elxp-backend.connectinskillz.com/elxp_files/public/courseImages/" . $this->image : null,
           
        ];
        // return parent::toArray($request);
    }
}
