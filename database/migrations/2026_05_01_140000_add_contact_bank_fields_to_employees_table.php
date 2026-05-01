<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('personal_email')->nullable()->after('work_email');
            $table->string('whatsapp_phone', 40)->nullable()->after('phone');
            $table->string('emergency_contact_relation', 120)->nullable()->after('emergency_contact_phone');

            $table->string('bank_name')->nullable()->after('emergency_contact_relation');
            $table->string('bank_branch')->nullable()->after('bank_name');
            $table->string('bank_account_title')->nullable()->after('bank_branch');
            $table->string('bank_account_number', 64)->nullable()->after('bank_account_title');
            $table->string('bank_iban', 34)->nullable()->after('bank_account_number');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'personal_email',
                'whatsapp_phone',
                'emergency_contact_relation',
                'bank_name',
                'bank_branch',
                'bank_account_title',
                'bank_account_number',
                'bank_iban',
            ]);
        });
    }
};
