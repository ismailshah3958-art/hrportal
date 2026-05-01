<script setup>
import { computed, reactive, onMounted } from 'vue';

const state = reactive({
    rows: [],
    loading: false,
    error: '',
    q: '',
    status: 'all',
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

function periodLabel(row) {
    const year = Number(row?.payroll_run?.period_year);
    const month = Number(row?.payroll_run?.period_month);
    if (!year || !month) return '—';
    try {
        return new Date(year, month - 1, 1).toLocaleString(undefined, {
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return `${year}-${String(month).padStart(2, '0')}`;
    }
}

const filteredRows = computed(() => {
    const q = state.q.trim().toLowerCase();
    return state.rows.filter((row) => {
        const status = row?.payroll_run?.status ?? '';
        if (state.status !== 'all' && status !== state.status) {
            return false;
        }
        if (!q) return true;

        const period = `${row?.payroll_run?.period_year ?? ''}-${String(row?.payroll_run?.period_month ?? '').padStart(2, '0')}`.toLowerCase();
        const net = String(Math.round(Number(row?.net_amount ?? 0))).toLowerCase();
        return period.includes(q) || periodLabel(row).toLowerCase().includes(q) || net.includes(q);
    });
});

const totals = computed(() => {
    return filteredRows.value.reduce(
        (acc, row) => {
            acc.gross += Number(row?.gross_amount ?? 0);
            acc.deductions += Number(row?.total_deductions ?? 0);
            acc.net += Number(row?.net_amount ?? 0);
            return acc;
        },
        { gross: 0, deductions: 0, net: 0 }
    );
});
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div>
            <h2 class="text-lg font-semibold text-white sm:text-xl">My payslips</h2>
            <p class="mt-1 text-sm text-slate-500">Monthly payroll lines available for your account.</p>
        </div>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3">
                <p class="text-xs uppercase tracking-wider text-slate-500">Gross total</p>
                <p class="mt-1 text-sm font-semibold text-white">{{ formatMoney(totals.gross) }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3">
                <p class="text-xs uppercase tracking-wider text-slate-500">Deductions total</p>
                <p class="mt-1 text-sm font-semibold text-amber-200">{{ formatMoney(totals.deductions) }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3">
                <p class="text-xs uppercase tracking-wider text-slate-500">Net total</p>
                <p class="mt-1 text-sm font-semibold text-amber-200">{{ formatMoney(totals.net) }}</p>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <input
                v-model.trim="state.q"
                type="text"
                placeholder="Search period or net…"
                class="sm:col-span-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500"
            >
            <select v-model="state.status" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <option value="all">All statuses</option>
                <option value="draft">Draft</option>
                <option value="finalized">Finalized</option>
                <option value="locked">Locked</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Period</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Gross</th>
                        <th class="px-4 py-3 text-right">Deduction</th>
                        <th class="px-4 py-3 text-right">Net</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <tr v-if="state.loading"><td colspan="6" class="px-4 py-10 text-center text-slate-500">Loading...</td></tr>
                    <tr v-else-if="!filteredRows.length"><td colspan="6" class="px-4 py-10 text-center text-slate-500">No payslip found.</td></tr>
                    <tr v-for="row in filteredRows" :key="row.id">
                        <td class="px-4 py-3 font-mono text-xs">{{ periodLabel(row) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-flex rounded-lg px-2 py-0.5 text-xs font-medium capitalize"
                                :class="row.payroll_run?.status === 'finalized' ? 'bg-amber-500/15 text-amber-300 ring-1 ring-amber-500/25' : 'bg-white/10 text-slate-300 ring-1 ring-white/10'"
                            >
                                {{ row.payroll_run?.status || '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">{{ formatMoney(row.gross_amount) }}</td>
                        <td class="px-4 py-3 text-right text-amber-200">{{ formatMoney(row.total_deductions) }}</td>
                        <td class="px-4 py-3 text-right text-amber-200">{{ formatMoney(row.net_amount) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a
                                v-if="row.payroll_run?.status === 'finalized'"
                                :href="`/api/my/payslips/${row.id}/download`"
                                class="inline-flex rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-medium text-amber-200 hover:bg-amber-500/20"
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
