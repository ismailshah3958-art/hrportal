<script setup>
import { reactive, onMounted } from 'vue';

const state = reactive({
    rows: [],
    loading: false,
    error: '',
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get('/api/my/payslips');
        state.rows = data.data ?? [];
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load payslips.';
    } finally {
        state.loading = false;
    }
}

onMounted(load);

function formatMoney(v) {
    const n = Number(v ?? 0);
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        maximumFractionDigits: 0,
    }).format(Number.isNaN(n) ? 0 : n);
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold text-white sm:text-xl">My payslips</h2>
            <p class="mt-1 text-sm text-slate-500">Monthly payroll lines available for your account.</p>
        </div>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3 text-right">Gross</th>
                        <th class="px-4 py-3 text-right">Deduction</th>
                        <th class="px-4 py-3 text-right">Net</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <tr v-if="state.loading"><td colspan="5" class="px-4 py-10 text-center text-slate-500">Loading...</td></tr>
                    <tr v-else-if="!state.rows.length"><td colspan="5" class="px-4 py-10 text-center text-slate-500">No payslip yet.</td></tr>
                    <tr v-for="row in state.rows" :key="row.id">
                        <td class="px-4 py-3 font-mono text-xs">{{ row.payroll_run?.period_year }}-{{ String(row.payroll_run?.period_month ?? '').padStart(2, '0') }}</td>
                        <td class="px-4 py-3 text-right">{{ formatMoney(row.gross_amount) }}</td>
                        <td class="px-4 py-3 text-right text-amber-200">{{ formatMoney(row.total_deductions) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-200">{{ formatMoney(row.net_amount) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a
                                v-if="row.payroll_run?.status === 'finalized'"
                                :href="`/api/my/payslips/${row.id}/download`"
                                class="inline-flex rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-200 hover:bg-emerald-500/20"
                            >
                                Download PDF
                            </a>
                            <span v-else class="text-xs text-slate-500">Available after finalize</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
