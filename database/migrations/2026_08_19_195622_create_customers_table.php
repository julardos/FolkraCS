<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->unique();
            $table->string('phone_lid')->nullable();
            $table->string('push_name')->nullable();
            $table->string('wa_session')->nullable();
            $table->boolean('is_human_takeover')->default(false);
            $table->foreignId('takeover_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
