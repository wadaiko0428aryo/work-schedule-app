<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();//承認した管理者のID

            $table->enum('status', ['pending', 'approved'])->default('pending');//状態を「pending（申請中）」か「approved（承認済み）」かでステータスを管理

            $table->dateTime('requested_break_start_time')->nullable();
            $table->dateTime('requested_break_end_time')->nullable();


            $table->json('edit_data')->nullable(); // 修正内容

            $table->timestamp('approved_at')->nullable();  //承認日時
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_requests');
    }
}
