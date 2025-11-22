<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail_url',
        'price',
        'status',
        'instructor_id',
        'category_id',
        'total_lessons',
        'total_duration',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total_lessons' => 'integer',
        'total_duration' => 'integer',
    ];

    protected $with = ['instructor', 'category'];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // Scope for published courses
    public function scopePublished($query)
    {
        return $query->where('status', 'PUBLISHED');
    }

    // Calculate average rating
    public function averageRating()
    {
        return $this->reviews()->avg('rating');
    }

    // Count total enrollments
    public function enrollmentCount()
    {
        return $this->enrollments()->count();
    }

    // Update total lessons and duration
    public function updateTotals()
    {
        $this->total_lessons = $this->lessons()->count();
        $this->total_duration = $this->lessons()->sum('duration');
        $this->save();
    }
}
