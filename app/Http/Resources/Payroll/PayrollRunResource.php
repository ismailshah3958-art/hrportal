<?php

namespace App\Http\Resources\Payroll;

use App\Models\PayrollRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PayrollRun */
class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_year' => (int) $this->period_year,
            'period_month' => (int) $this->period_month,
            'period_start' => $this->period_start?->format('Y-m-d'),
            'period_end' => $this->period_end?->format('Y-m-d'),
            'status' => $this->status,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'notes' => $this->notes,
            'items_count' => $this->whenCounted('items', fn () => (int) $this->items_count),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
