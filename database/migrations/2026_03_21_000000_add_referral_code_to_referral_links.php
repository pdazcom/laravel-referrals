<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_links', function (Blueprint $table) {
            $table->string('referral_code', 32)->nullable()->unique()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('referral_links', function (Blueprint $table) {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};
