<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'salary_structure_id',
        'gross_amount',
        'total_allowances',
        'total_deductions',
        'net_amount',
        'late_incidents',
        'late_deduction_days',
        'late_deduction_amount',
        'breakdown_json',
        'attendance_attachment_json',
        'payslip_path',
        'payslip_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'total_allowances' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'late_incidents' => 'integer',
            'late_deduction_days' => 'decimal:4',
            'late_deduction_amount' => 'decimal:2',
            'payslip_generated_at' => 'datetime',
        ];
    }

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
