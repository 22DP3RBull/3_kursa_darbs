<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('action'); // 'check-in' or 'check-out'
            $table->dateTime('performed_at');
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });

        // Add columns to the students table for last check-in and check-out timestamps
        Schema::table('students', function (Blueprint $table) {
            $table->dateTime('last_check_in')->nullable()->after('checkedIn');
            $table->dateTime('last_check_out')->nullable()->after('last_check_in');
        });
    }

    public function down()
    {
        Schema::dropIfExists('history');

        // Remove the added columns from the students table
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['last_check_in', 'last_check_out']);
        });
    }
}
