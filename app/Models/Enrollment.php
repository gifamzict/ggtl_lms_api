<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'enrolled_at',
        'completed_at',
        'progress_percentage',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'decimal:2',
    ];

    protected $with = ['course'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // Calculate progress based on completed lessons
    public function calculateProgress()
    {
        $totalLessons = $this->course->lessons()->count();
        
        if ($totalLessons == 0) {
            return 0;
        }

        $completedLessons = LessonProgress::where('user_id', $this->user_id)
            ->whereIn('lesson_id', $this->course->lessons()->pluck('id'))
            ->where('completed', true)
            ->count();

        $percentage = ($completedLessons / $totalLessons) * 100;

        $this->progress_percentage = $percentage;

        // Mark as completed if 100%
        if ($percentage >= 100 && !$this->completed_at) {
            $this->completed_at = now();
        }

        $this->save();

        return $percentage;
    }
}
