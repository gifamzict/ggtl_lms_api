<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->enum('video_source', ['UPLOAD', 'DRIVE', 'YOUTUBE', 'VIMEO']);
            $table->string('video_url');
            $table->integer('duration')->default(0); // in seconds
            $table->integer('position')->default(0);
            $table->boolean('is_preview')->default(false);
            $table->timestamps();
            
            $table->index('course_id');
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
