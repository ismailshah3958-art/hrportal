<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_review_id')->constrained()->cascadeOnDelete();
            $table->foreignId('kpi_definition_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 7, 2);
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['performance_review_id', 'kpi_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_scores');
    }
};
