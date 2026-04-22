<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'course_contents';

    protected $hidden = ['created_at', 'updated_at'];

    public function contentable()
    {
        return $this->morphTo();
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'uuid');
    }

    public function getFileAttribute($value)
    {
        if (!$value) return null;

        $baseUrl = 'https://elxp-backend.connectinskillz.com/new_elxp_files/public/contentFiles/';

        // If JSON array
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_map(function ($file) use ($baseUrl) {
                return str_starts_with($file, 'http') ? $file : $baseUrl . $file;
            }, $decoded);
        }

        // Single file
        return str_starts_with($value, 'http') ? $value : $baseUrl . $value;
    }
}
