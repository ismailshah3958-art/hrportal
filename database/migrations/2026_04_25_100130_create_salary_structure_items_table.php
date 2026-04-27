<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_structure_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20); // allowance, deduction
            $table->string('calculation_type', 30)->default('fixed'); // fixed, percent_of_basic
            $table->decimal('amount', 14, 2)->nullable();
            $table->decimal('percent', 7, 4)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_structure_items');
    }
};
