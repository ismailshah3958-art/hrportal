<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_review_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('draft'); // draft, active, closed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_review_cycles');
    }
};
