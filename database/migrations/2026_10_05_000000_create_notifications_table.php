<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // 'employee' -> employees.id | 'student' -> students.student_id (e.g. 26-00063)
            $table->string('recipient_type', 20);
            $table->string('recipient_id', 50);
            $table->string('type', 60);
            $table->string('title');
            $table->text('message');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id', 'read_at'], 'notif_recipient_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
