<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('mode', 30)->default('video'); // in_person, video, phone
            $table->string('location')->nullable();
            $table->foreignId('interviewer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status', 30)->default('scheduled'); // scheduled, completed, cancelled, no_show
            $table->text('feedback')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
