<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date')->nullable();

            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();

            $table->text('reason')->nullable();

            $table->boolean('is_approval')->default(false); //勤怠情報が管理者により承認されたか確認する。

            $table->enum('status', ['pending', 'approved'])->default('pending');//状態を「pending（申請中）」か「approved（承認済み）」かでステータスを管理

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
