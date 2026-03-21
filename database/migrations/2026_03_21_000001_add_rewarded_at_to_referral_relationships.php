<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_relationships', function (Blueprint $table) {
            $table->timestamp('rewarded_at')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('referral_relationships', function (Blueprint $table) {
            $table->dropColumn('rewarded_at');
        });
    }
};
