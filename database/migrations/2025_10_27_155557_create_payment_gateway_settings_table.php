<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->text('paystack_public_key_encrypted');
            $table->text('paystack_secret_key_encrypted');
            $table->boolean('is_live_mode')->default(false);
            $table->timestamps();
        });
        
        // Insert default row
        DB::table('payment_gateway_settings')->insert([
            'paystack_public_key_encrypted' => '',
            'paystack_secret_key_encrypted' => '',
            'is_live_mode' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
