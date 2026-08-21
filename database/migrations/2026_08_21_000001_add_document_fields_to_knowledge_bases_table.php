<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->enum('type', ['text', 'document'])->default('text')->after('title');
            $table->string('file_name')->nullable()->after('type');
            $table->string('file_path')->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropColumn(['type', 'file_name', 'file_path']);
        });
    }
};
