<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('holiday_date');
            $table->string('type')->default('public'); // public, company, optional
            $table->boolean('is_optional')->default(false);
            $table->string('country_code', 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('holiday_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
