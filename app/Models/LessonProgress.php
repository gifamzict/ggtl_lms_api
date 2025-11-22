<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonProgress extends Model
{
    use HasFactory;

    protected $table = 'lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'completed',
        'watched_duration',
        'completed_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'watched_duration' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    // Mark as completed when watched duration >= lesson duration
    public function checkCompletion()
    {
        if ($this->watched_duration >= $this->lesson->duration && !$this->completed) {
            $this->completed = true;
            $this->completed_at = now();
            $this->save();

            // Recalculate enrollment progress
            $enrollment = Enrollment::where('user_id', $this->user_id)
                ->where('course_id', $this->lesson->course_id)
                ->first();

            if ($enrollment) {
                $enrollment->calculateProgress();
            }
        }
    }

    // Boot method to trigger completion check
    protected static function booted()
    {
        static::saved(function ($progress) {
            $progress->checkCompletion();
        });
    }
}
