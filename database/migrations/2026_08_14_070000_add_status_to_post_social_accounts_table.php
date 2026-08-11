<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('post_social_accounts', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('social_account_id');
            $table->string('remote_id')->nullable()->after('settings');
            $table->timestamp('published_at')->nullable()->after('remote_id');
            $table->text('error')->nullable()->after('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_social_accounts', function (Blueprint $table) {
            $table->dropColumn(['status', 'remote_id', 'published_at', 'error']);
        });
    }
};
