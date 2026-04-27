<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedInteger('zk_badge_user_id')->nullable()->after('employee_code');
            $table->unique('zk_badge_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['zk_badge_user_id']);
            $table->dropColumn('zk_badge_user_id');
        });
    }
};
