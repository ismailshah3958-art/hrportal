<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('proposed_last_working_date');
            $table->text('reason');
            $table->string('status', 40)->default('draft'); // draft, submitted, approved, rejected, clearance, settled
            $table->json('clearance_checklist')->nullable();
            $table->decimal('final_settlement_amount', 14, 2)->nullable();
            $table->text('settlement_notes')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_requests');
    }
};
