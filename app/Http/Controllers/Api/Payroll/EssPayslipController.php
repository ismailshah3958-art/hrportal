<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payroll\PayrollItemResource;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EssPayslipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('ess.payslip.view') && $user->employee, 403);

        $items = PayrollItem::query()
            ->where('employee_id', $user->employee->id)
            ->with('payrollRun')
            ->orderByDesc('id')
            ->limit(24)
            ->get();

        return response()->json([
            'data' => PayrollItemResource::collection($items)->resolve(),
        ]);
    }

    public function download(Request $request, PayrollItem $payrollItem): Response
    {
        $user = $request->user();
        abort_unless($user?->can('ess.payslip.view') && $user->employee, 403);

        $payrollItem->loadMissing('employee.department', 'employee.designation', 'payrollRun');
        abort_unless((int) $payrollItem->employee_id === (int) $user->employee->id, 404);
        abort_unless($payrollItem->payrollRun && $payrollItem->payrollRun->status === 'finalized', 422, 'Payslip is available only after payroll finalize.');

        $breakdown = [];
        if ($payrollItem->breakdown_json) {
            $decoded = json_decode((string) $payrollItem->breakdown_json, true);
            if (is_array($decoded)) {
                $breakdown = $decoded;
            }
        }

        $pdf = Pdf::loadView('payslips.employee', [
            'item' => $payrollItem,
            'employee' => $payrollItem->employee,
            'run' => $payrollItem->payrollRun,
            'breakdown' => $breakdown,
            'generatedAt' => now(),
        ])->setPaper('a4');

        $filename = sprintf(
            'payslip-%s-%d-%02d.pdf',
            strtolower((string) ($payrollItem->employee?->employee_code ?? $payrollItem->employee_id)),
            (int) $payrollItem->payrollRun->period_year,
            (int) $payrollItem->payrollRun->period_month
        );

        return $pdf->download($filename);
    }
}
