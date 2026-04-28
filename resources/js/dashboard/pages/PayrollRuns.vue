<script setup>
import { reactive, onMounted } from 'vue';

const state = reactive({
    rows: [],
    meta: null,
    loading: false,
    error: '',
    runMonth: new Date().toISOString().slice(0, 7),
    selectedRun: null,
    runItems: [],
    summary: null,
    loadingItems: false,
    actionMessage: '',
    savingItemId: null,
    finalizing: false,
});

const itemForm = reactive({
    id: null,
    gross_amount: '',
    adjustment_allowance: '',
    adjustment_deduction: '',
    note: '',
});

async function loadRuns(page = 1) {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get('/api/payroll/runs', { params: { page } });
        state.rows = data.data ?? [];
        state.meta = data.meta ?? null;
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load payroll runs.';
    } finally {
        state.loading = false;
    }
}

onMounted(() => loadRuns());

async function generateRun() {
    state.actionMessage = '';
    state.error = '';
    const [year, month] = state.runMonth.split('-').map(Number);
    try {
        const { data } = await window.axios.post('/api/payroll/runs', {
            period_year: year,
            period_month: month,
        });
        state.actionMessage = data.message ?? 'Payroll run generated.';
        await loadRuns(1);
        if (data.run?.id) {
            await openRun(data.run);
        }
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not generate payroll run.';
    }
}

async function openRun(run) {
    state.selectedRun = run;
    state.loadingItems = true;
    state.error = '';
    clearItemForm();
    try {
        const { data } = await window.axios.get(`/api/payroll/runs/${run.id}`);
        state.selectedRun = data.run ?? run;
        state.runItems = data.items ?? [];
        state.summary = data.summary ?? null;
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load payroll items.';
        state.runItems = [];
        state.summary = null;
    } finally {
        state.loadingItems = false;
    }
}

function selectItem(it) {
    itemForm.id = it.id;
    itemForm.gross_amount = String(it.gross_amount ?? '0');
    itemForm.adjustment_allowance = String(it.breakdown?.adjustment_allowance ?? '0');
    itemForm.adjustment_deduction = String(it.breakdown?.adjustment_deduction ?? '0');
    itemForm.note = String(it.breakdown?.note ?? '');
}

function clearItemForm() {
    itemForm.id = null;
    itemForm.gross_amount = '';
    itemForm.adjustment_allowance = '';
    itemForm.adjustment_deduction = '';
    itemForm.note = '';
}

async function saveItemAdjustments() {
    if (!state.selectedRun || !itemForm.id) return;
    state.savingItemId = itemForm.id;
    state.error = '';
    state.actionMessage = '';

    try {
        const payload = {
            gross_amount: Number(itemForm.gross_amount || 0),
            adjustment_allowance: Number(itemForm.adjustment_allowance || 0),
            adjustment_deduction: Number(itemForm.adjustment_deduction || 0),
            note: itemForm.note,
        };
        const { data } = await window.axios.put(`/api/payroll/runs/${state.selectedRun.id}/items/${itemForm.id}`, payload);
        state.actionMessage = data.message ?? 'Payroll item updated.';
        await openRun(state.selectedRun);
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not update payroll item.';
    } finally {
        state.savingItemId = null;
    }
}

async function finalizeRun() {
    if (!state.selectedRun) return;
    if (!window.confirm('Finalize this payroll run? You cannot edit items after finalize.')) return;

    state.finalizing = true;
    state.error = '';
    state.actionMessage = '';
    try {
        const { data } = await window.axios.post(`/api/payroll/runs/${state.selectedRun.id}/finalize`);
        state.actionMessage = data.message ?? 'Payroll run finalized.';
        await loadRuns(1);
        await openRun(state.selectedRun);
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not finalize run.';
    } finally {
        state.finalizing = false;
    }
}

function exportRun() {
    if (!state.selectedRun) return;
    window.open(`/api/payroll/runs/${state.selectedRun.id}/export`, '_blank');
}

function formatMoney(v) {
    const n = Number(v ?? 0);
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        maximumFractionDigits: 0,
    }).format(Number.isNaN(n) ? 0 : n);
}

function isSelectedItem(it) {
    return itemForm.id === it.id;
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Payroll runs</h2>
                <p class="mt-1 text-sm text-slate-500">Generate, adjust, finalize and export monthly payroll.</p>
            </div>
            <div class="flex items-center gap-2">
                <input v-model="state.runMonth" type="month" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <button type="button" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500" @click="generateRun">Generate run</button>
            </div>
        </div>

        <div v-if="state.actionMessage" class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ state.actionMessage }}</div>
        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <div v-if="state.summary" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">Total Gross</p>
                <p class="mt-1 text-lg font-semibold text-white">{{ formatMoney(state.summary.gross) }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">Total Deductions</p>
                <p class="mt-1 text-lg font-semibold text-amber-200">{{ formatMoney(state.summary.deductions) }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">Net Pay</p>
                <p class="mt-1 text-lg font-semibold text-emerald-200">{{ formatMoney(state.summary.net) }}</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4">
                <p class="text-xs uppercase tracking-wider text-slate-500">Zero Salary Employees</p>
                <p class="mt-1 text-lg font-semibold" :class="state.summary.zero_salary_count > 0 ? 'text-amber-200' : 'text-white'">{{ state.summary.zero_salary_count }}</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.05fr_1.45fr]">
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <div class="border-b border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white">Runs</div>
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-2.5">Period</th>
                            <th class="px-4 py-2.5">Status</th>
                            <th class="px-4 py-2.5">Items</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <tr v-if="state.loading"><td colspan="3" class="px-4 py-8 text-center text-slate-500">Loading...</td></tr>
                        <tr v-else-if="!state.rows.length"><td colspan="3" class="px-4 py-8 text-center text-slate-500">No payroll run yet.</td></tr>
                        <tr v-for="r in state.rows" :key="r.id" class="cursor-pointer hover:bg-white/[0.04]" @click="openRun(r)">
                            <td class="px-4 py-3 font-mono text-xs">{{ r.period_year }}-{{ String(r.period_month).padStart(2, '0') }}</td>
                            <td class="px-4 py-3 capitalize">{{ r.status }}</td>
                            <td class="px-4 py-3">{{ r.items_count ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3">
                    <p class="text-sm font-semibold text-white">{{ state.selectedRun ? `Items for ${state.selectedRun.period_year}-${String(state.selectedRun.period_month).padStart(2, '0')}` : 'Run items' }}</p>
                    <div class="flex gap-2">
                        <button type="button" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/5 disabled:opacity-40" :disabled="!state.selectedRun" @click="exportRun">Export CSV</button>
                        <button type="button" class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-500 disabled:opacity-40" :disabled="!state.selectedRun || state.selectedRun.status !== 'draft' || state.finalizing" @click="finalizeRun">Finalize</button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                    <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                        <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="px-4 py-2.5">Employee</th>
                                <th class="px-4 py-2.5 text-right">Gross</th>
                                <th class="px-4 py-2.5 text-right">Allowance</th>
                                <th class="px-4 py-2.5 text-right">Deduction</th>
                                <th class="px-4 py-2.5 text-right">Net</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-slate-300">
                            <tr v-if="state.loadingItems"><td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading...</td></tr>
                            <tr v-else-if="!state.runItems.length"><td colspan="5" class="px-4 py-8 text-center text-slate-500">Select a run to see items.</td></tr>
                            <tr v-for="it in state.runItems" :key="it.id" class="cursor-pointer" :class="isSelectedItem(it) ? 'bg-emerald-900/20' : 'hover:bg-white/[0.04]'" @click="selectItem(it)">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-white">{{ it.employee?.full_name ?? 'Employee' }}</span>
                                    <span class="ml-2 font-mono text-xs text-slate-500">{{ it.employee?.employee_code }}</span>
                                    <span v-if="(Number(it.gross_amount) || 0) <= 0" class="ml-2 rounded bg-amber-500/20 px-1.5 py-0.5 text-[10px] text-amber-200">No salary</span>
                                </td>
                                <td class="px-4 py-3 text-right">{{ formatMoney(it.gross_amount) }}</td>
                                <td class="px-4 py-3 text-right">{{ formatMoney(it.total_allowances) }}</td>
                                <td class="px-4 py-3 text-right text-amber-200">{{ formatMoney(it.total_deductions) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-200">{{ formatMoney(it.net_amount) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <form v-if="state.selectedRun && itemForm.id" class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-2" @submit.prevent="saveItemAdjustments">
                    <div>
                        <label class="mb-1 block text-xs uppercase tracking-wider text-slate-500">Gross</label>
                        <input v-model="itemForm.gross_amount" type="number" min="0" step="0.01" :disabled="state.selectedRun.status !== 'draft'" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs uppercase tracking-wider text-slate-500">Allowance Adjustment</label>
                        <input v-model="itemForm.adjustment_allowance" type="number" min="0" step="0.01" :disabled="state.selectedRun.status !== 'draft'" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs uppercase tracking-wider text-slate-500">Deduction Adjustment</label>
                        <input v-model="itemForm.adjustment_deduction" type="number" min="0" step="0.01" :disabled="state.selectedRun.status !== 'draft'" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs uppercase tracking-wider text-slate-500">Note</label>
                        <input v-model="itemForm.note" type="text" maxlength="1000" :disabled="state.selectedRun.status !== 'draft'" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </div>
                    <div class="sm:col-span-2 flex gap-2">
                        <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-40" :disabled="state.selectedRun.status !== 'draft' || state.savingItemId === itemForm.id">Save adjustment</button>
                        <button type="button" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/5" @click="clearItemForm">Clear</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>