<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'course_id',
        'video_source',
        'video_url',
        'duration',
        'position',
        'is_preview',
    ];

    protected $casts = [
        'duration' => 'integer',
        'position' => 'integer',
        'is_preview' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    // Get progress for specific user
    public function progressForUser($userId)
    {
        return $this->progress()->where('user_id', $userId)->first();
    }

    // Boot method to update course totals after lesson changes
    protected static function booted()
    {
        static::created(function ($lesson) {
            $lesson->course->updateTotals();
        });

        static::updated(function ($lesson) {
            $lesson->course->updateTotals();
        });

        static::deleted(function ($lesson) {
            $lesson->course->updateTotals();
        });
    }
}
