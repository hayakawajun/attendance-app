<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('target_date');
            $table->string('status')->default('pending')->index()
                ->comment('pending:承認待ち,approved:承認済み');
            $table->boolean('is_deletion')->default(false);
            $table->string('reason');
            $table->datetime('requested_at')->useCurrent();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('approved_by_name')->nullable()->comment('承認時の管理者名');
            $table->datetime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_requests');
    }
}
