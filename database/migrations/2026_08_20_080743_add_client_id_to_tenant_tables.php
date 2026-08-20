<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['knowledge_bases', 'notification_settings', 'prompt_blocks'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('id');
                $table->index('client_id');
            });
        }

        foreach (['customers', 'conversations', 'support_tickets'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedBigInteger('client_id')->nullable()->after('id');
                $table->index('client_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['knowledge_bases', 'notification_settings', 'prompt_blocks', 'customers', 'conversations', 'support_tickets'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropIndex(['client_id']);
                $table->dropColumn('client_id');
            });
        }
    }
};
