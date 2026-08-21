<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('instagram_account_id')->nullable()->after('wa_session');
            $table->string('instagram_username')->nullable()->after('instagram_account_id');
            $table->text('instagram_access_token')->nullable()->after('instagram_username');
            $table->timestamp('instagram_token_expires_at')->nullable()->after('instagram_access_token');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_account_id',
                'instagram_username',
                'instagram_access_token',
                'instagram_token_expires_at',
            ]);
        });
    }
};
