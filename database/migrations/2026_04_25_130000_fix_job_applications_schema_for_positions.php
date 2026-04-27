<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_applications')) {
            return;
        }

        if (Schema::hasColumn('job_applications', 'job_position_id')) {
            return;
        }

        if (Schema::hasTable('interviews')) {
            Schema::drop('interviews');
        }

        $legacyName = 'job_applications_legacy_ats_20260425';
        $rowCount = (int) DB::table('job_applications')->count();

        if ($rowCount > 0) {
            if (Schema::hasTable($legacyName)) {
                Schema::drop($legacyName);
            }
            Schema::rename('job_applications', $legacyName);
        } else {
            Schema::drop('job_applications');
        }

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_position_id')->constrained('job_positions')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('stage', 30)->default('applied');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['job_position_id', 'stage']);
        });

        if (! Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('job_application_id')->constrained('job_applications')->cascadeOnDelete();
                $table->dateTime('scheduled_at');
                $table->unsignedSmallInteger('duration_minutes')->nullable();
                $table->string('mode', 30)->default('video');
                $table->string('location')->nullable();
                $table->foreignId('interviewer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('status', 30)->default('scheduled');
                $table->text('feedback')->nullable();
                $table->unsignedTinyInteger('rating')->nullable();
                $table->timestamps();

                $table->index(['scheduled_at', 'status']);
            });
        }
    }

    public function down(): void
    {
        // Non-reversible safely; legacy table kept if renamed.
    }
};
