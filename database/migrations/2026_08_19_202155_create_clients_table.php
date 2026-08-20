<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('business_type')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            // WA config
            $table->string('wa_base_url')->nullable();
            $table->string('wa_api_key')->nullable();
            $table->string('wa_session')->nullable();
            // AI config
            $table->string('openrouter_api_key')->nullable();
            $table->string('openrouter_model')->default('openai/gpt-4o-mini');
            $table->longText('ai_instruction')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
