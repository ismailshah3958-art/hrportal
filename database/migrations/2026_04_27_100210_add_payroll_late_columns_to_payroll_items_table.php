<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->unsignedInteger('late_incidents')->default(0)->after('total_deductions');
            $table->decimal('late_deduction_days', 8, 4)->default(0)->after('late_incidents');
            $table->decimal('late_deduction_amount', 14, 2)->default(0)->after('late_deduction_days');
            $table->longText('attendance_attachment_json')->nullable()->after('breakdown_json');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'late_incidents',
                'late_deduction_days',
                'late_deduction_amount',
                'attendance_attachment_json',
            ]);
        });
    }
};
