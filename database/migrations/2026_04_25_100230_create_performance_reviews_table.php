<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performance_review_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->text('employee_self_notes')->nullable();
            $table->text('manager_notes')->nullable();
            $table->decimal('overall_rating', 4, 2)->nullable();
            $table->string('status', 30)->default('draft'); // draft, submitted, approved
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'performance_review_cycle_id'], 'perf_reviews_emp_cycle_uidx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
