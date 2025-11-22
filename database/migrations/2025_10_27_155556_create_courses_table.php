<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['DRAFT', 'PUBLISHED', 'ARCHIVED'])->default('DRAFT');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->integer('total_lessons')->default(0);
            $table->integer('total_duration')->default(0); // in seconds
            $table->timestamps();
            
            $table->index('slug');
            $table->index('status');
            $table->index('instructor_id');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
