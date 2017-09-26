<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAllowedRefProgramToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (app()->environment() === 'testing') {
            return;
        }

        Schema::table($this->getUsersTable(), function (Blueprint $table) {
            $table->integer('referral_program_id')->unsigned()->nullable()->dafault(null);

            $table->foreign('referral_program_id')
                ->references('id')
                ->on('referral_programs')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (app()->environment() === 'testing') {
            return;
        }

        Schema::table($this->getUsersTable(), function (Blueprint $table) {
            $table->dropForeign(['referral_program_id']);
            $table->dropColumn('referral_program_id');
        });
    }

    private function getUsersTable()
    {
        $userModel = config('auth.providers.users.model');
        return (new $userModel)->getTable();
    }
}
