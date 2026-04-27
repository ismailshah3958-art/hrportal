<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { margin-bottom: 18px; }
        .title { font-size: 20px; font-weight: 700; margin: 0; }
        .muted { color: #6b7280; }
        .grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grid th, .grid td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        .grid th { background: #f9fafb; font-weight: 600; }
        .right { text-align: right; }
        .totals { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .totals td { padding: 6px 8px; }
        .totals .label { width: 70%; }
        .totals .value { text-align: right; width: 30%; }
        .net { font-weight: 700; font-size: 14px; border-top: 2px solid #111827; }
        .footer { margin-top: 24px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">Payslip</p>
        <p class="muted">Generated: {{ $generatedAt->format('Y-m-d H:i') }}</p>
    </div>

    <table class="grid">
        <tr>
            <th>Employee</th>
            <td>{{ $employee->full_name }} ({{ $employee->employee_code }})</td>
            <th>Period</th>
            <td>{{ $run->period_year }}-{{ str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <th>Department</th>
            <td>{{ $employee->department?->name ?? '-' }}</td>
            <th>Designation</th>
            <td>{{ $employee->designation?->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount (PKR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gross Salary</td>
                <td class="right">{{ number_format((float) $item->gross_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Manual Allowance Adjustment</td>
                <td class="right">{{ number_format((float) ($breakdown['adjustment_allowance'] ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Late Deduction</td>
                <td class="right">{{ number_format((float) $item->late_deduction_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Manual Deduction Adjustment</td>
                <td class="right">{{ number_format((float) ($breakdown['adjustment_deduction'] ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Total Allowances</td>
            <td class="value">{{ number_format((float) $item->total_allowances, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Total Deductions</td>
            <td class="value">{{ number_format((float) $item->total_deductions, 2) }}</td>
        </tr>
        <tr class="net">
            <td class="label">Net Pay</td>
            <td class="value">{{ number_format((float) $item->net_amount, 2) }}</td>
        </tr>
    </table>

    <p class="footer">
        Note: {{ $breakdown['note'] ?? 'N/A' }}
    </p>
</body>
</html>