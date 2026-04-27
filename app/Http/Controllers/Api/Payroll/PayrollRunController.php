<?php

namespace App\Http\Controllers\Api\Payroll;

use App\Http\Controllers\Controller;
use App\Http\Resources\Payroll\PayrollItemResource;
use App\Http\Resources\Payroll\PayrollRunResource;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Services\Payroll\PayrollAttendanceAttachment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()?->can('hr.payroll.manage'), 403);

        $runs = PayrollRun::query()
            ->withCount('items')
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(12);

        return PayrollRunResource::collection($runs);
    }

    public function show(Request $request, PayrollRun $payrollRun)
    {
        abort_unless($request->user()?->can('hr.payroll.manage'), 403);

        $items = PayrollItem::query()
            ->where('payroll_run_id', $payrollRun->id)
            ->with('employee')
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'run' => (new PayrollRunResource($payrollRun->loadCount('items')))->resolve(),
            'items' => PayrollItemResource::collection($items)->resolve(),
            'summary' => $this->buildSummary($items),
        ]);
    }

    public function store(Request $request, PayrollAttendanceAttachment $attendance): JsonResponse
    {
        abort_unless($request->user()?->can('hr.payroll.manage'), 403);

        $data = $request->validate([
            'period_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $start = Carbon::createFromDate((int) $data['period_year'], (int) $data['period_month'], 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $run = PayrollRun::query()->firstOrCreate(
            [
                'period_year' => (int) $data['period_year'],
                'period_month' => (int) $data['period_month'],
            ],
            [
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => 'draft',
                'processed_by_user_id' => $request->user()->id,
                'processed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]
        );

        if (in_array($run->status, ['finalized', 'locked'], true)) {
            return response()->json([
                'message' => 'This payroll run is finalized. Create a new period or unlock policy first.',
            ], 422);
        }

        $employees = Employee::query()->where('status', 'active')->get();

        foreach ($employees as $employee) {
            $salary = (float) ($employee->salary ?? 0);

            $item = PayrollItem::query()->updateOrCreate(
                [
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                ],
                [
                    'gross_amount' => $salary,
                    'total_allowances' => 0,
                    'total_deductions' => 0,
                    'net_amount' => $salary,
                ]
            );

            $this->recalculateItem($item, $attendance);
        }

        return response()->json([
            'message' => 'Payroll run generated.',
            'run' => (new PayrollRunResource($run->fresh()->loadCount('items')))->resolve(),
        ]);
    }

    public function updateItem(
        Request $request,
        PayrollRun $payrollRun,
        PayrollItem $payrollItem,
        PayrollAttendanceAttachment $attendance
    ): JsonResponse {
        abort_unless($request->user()?->can('hr.payroll.manage'), 403);
        abort_unless((int) $payrollItem->payroll_run_id === (int) $payrollRun->id, 404);

        if ($payrollRun->status !== 'draft') {
            return response()->json(['message' => 'Only draft runs can be edited.'], 422);
        }

        $data = $request->validate([
            'gross_amount' => ['nullable', 'numeric', 'min:0'],
            'adjustment_allowance' => ['nullable', 'numeric', 'min:0'],
            'adjustment_deduction' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (array_key_exists('gross_amount', $data) && $data['gross_amount'] !== null) {
            $payrollItem->gross_amount = (float) $data['gross_amount'];
        }

        $breakdown = [];
        if ($payrollItem->breakdown_json) {
            $decoded = json_decode((string) $payrollItem->breakdown_json, true);
            if (is_array($decoded)) {
                $breakdown = $decoded;
            }
        }

        $breakdown['adjustment_allowance'] = (float) ($data['adjustment_allowance'] ?? ($breakdown['adjustment_allowance'] ?? 0));
        $breakdown['adjustment_deduction'] = (float) ($data['adjustment_deduction'] ?? ($breakdown['adjustment_deduction'] ?? 0));
        $breakdown['note'] = (string) ($data['note'] ?? ($breakdown['note'] ?? ''));
        $breakdown['updated_at'] = now()->toIso8601String();
        $payrollItem->breakdown_json = json_encode($breakdown, JSON_THROW_ON_ERROR);
        $payrollItem->save();

        $this->recalculateItem($payrollItem, $attendance);

        return response()->json([
            'message' => 'Payroll item updated.',
            'item' => (new PayrollItemResource($payrollItem->fresh()->load(['employee', 'payrollRun'])))->resolve(),
        ]);
    }

    public function finalize(Request $request, PayrollRun $payrollRun): JsonResponse
    {
        abort_unless($request->user()?->can('hr.payroll.manage'), 403);

        if ($payrollRun->status !== 'draft') {
            return response()->json(['message' => 'This payroll run is already finalized.'], 422);
        }

        $count = PayrollItem::query()->where('payroll_run_id', $payrollRun->id)->count();
        if ($count === 0) {
            return response()->json(['message' => 'Cannot finalize an empty payroll run.'], 422);
        }

        $payrollRun->status = 'finalized';
        $payrollRun->processed_by_user_id = $request->user()->id;
        $payrollRun->processed_at = now();
        $payrollRun->save();

        return response()->json([
            'message' => 'Payroll run finalized.',
            'run' => (new PayrollRunResource($payrollRun->fresh()->loadCount('items')))->resolve(),
        ]);
    }

    public function exportCsv(Request $request, PayrollRun $payrollRun): StreamedResponse
    {
        abort_unless($request->user()?->can('hr.payroll.manage'), 403);

        $items = PayrollItem::query()
            ->where('payroll_run_id', $payrollRun->id)
            ->with('employee')
            ->orderBy('employee_id')
            ->get();

        $filename = sprintf('payroll-%d-%02d.csv', $payrollRun->period_year, $payrollRun->period_month);

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['Employee Code', 'Employee Name', 'Gross', 'Allowances', 'Deductions', 'Net', 'Late Incidents']);
            foreach ($items as $item) {
                fputcsv($out, [
                    (string) ($item->employee?->employee_code ?? ''),
                    (string) ($item->employee?->full_name ?? ''),
                    (string) $item->gross_amount,
                    (string) $item->total_allowances,
                    (string) $item->total_deductions,
                    (string) $item->net_amount,
                    (string) ($item->late_incidents ?? 0),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function recalculateItem(PayrollItem $item, PayrollAttendanceAttachment $attendance): void
    {
        $attendance->applyToPayrollItem($item);
        $item->refresh();

        $breakdown = [];
        if ($item->breakdown_json) {
            $decoded = json_decode((string) $item->breakdown_json, true);
            if (is_array($decoded)) {
                $breakdown = $decoded;
            }
        }

        $adjAllowance = (float) ($breakdown['adjustment_allowance'] ?? 0);
        $adjDeduction = (float) ($breakdown['adjustment_deduction'] ?? 0);
        $lateDeduction = (float) ($item->late_deduction_amount ?? 0);

        $item->total_allowances = round($adjAllowance, 2);
        $item->total_deductions = round($lateDeduction + $adjDeduction, 2);
        $item->net_amount = round(max(0, (float) $item->gross_amount + (float) $item->total_allowances - (float) $item->total_deductions), 2);
        $item->save();
    }

    private function buildSummary($items): array
    {
        $gross = 0.0;
        $allowances = 0.0;
        $deductions = 0.0;
        $net = 0.0;
        $lateIncidents = 0;
        $zeroSalary = 0;

        foreach ($items as $item) {
            $g = (float) $item->gross_amount;
            $gross += $g;
            $allowances += (float) $item->total_allowances;
            $deductions += (float) $item->total_deductions;
            $net += (float) $item->net_amount;
            $lateIncidents += (int) ($item->late_incidents ?? 0);
            if ($g <= 0) {
                $zeroSalary++;
            }
        }

        return [
            'employees' => count($items),
            'gross' => round($gross, 2),
            'allowances' => round($allowances, 2),
            'deductions' => round($deductions, 2),
            'net' => round($net, 2),
            'late_incidents' => $lateIncidents,
            'zero_salary_count' => $zeroSalary,
        ];
    }
}
