<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->after('email');
            $table->enum('role', ['STUDENT', 'INSTRUCTOR', 'ADMIN', 'SUPER_ADMIN'])->default('STUDENT')->after('full_name');
            $table->text('bio')->nullable()->after('role');
            $table->string('avatar_url')->nullable()->after('bio');
            $table->string('phone')->nullable()->after('avatar_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'role', 'bio', 'avatar_url', 'phone']);
        });
    }
};
