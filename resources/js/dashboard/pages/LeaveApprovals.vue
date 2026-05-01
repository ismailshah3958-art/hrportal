<script setup>
import { reactive, onMounted } from 'vue';

const state = reactive({
    rows: [],
    loading: true,
    error: '',
    actingId: null,
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get('/api/leave-requests/pending');
        state.rows = data.data ?? [];
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load pending requests.';
        state.rows = [];
    } finally {
        state.loading = false;
    }
}

onMounted(load);

async function approve(row) {
    if (!window.confirm(`Approve leave for ${row.employee?.full_name ?? 'employee'}?`)) return;

    state.actingId = row.id;
    try {
        await window.axios.post(`/api/leave-requests/${row.id}/approve`);
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Approve failed.';
    } finally {
        state.actingId = null;
    }
}

async function reject(row) {
    const note = window.prompt(`Reject leave for ${row.employee?.full_name ?? 'employee'}?\nOptional note:`, '');
    if (note === null) return;

    state.actingId = row.id;
    try {
        await window.axios.post(`/api/leave-requests/${row.id}/reject`, { hr_notes: note || null });
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Reject failed.';
    } finally {
        state.actingId = null;
    }
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Leave approvals</h2>
                <p class="mt-1 text-sm text-slate-500">Pending requests for your team (manager) or all staff (HR).</p>
            </div>
            <button type="button" class="rounded-xl border border-white/10 px-3 py-2 text-sm text-slate-300 hover:bg-white/5" @click="load">Refresh</button>
        </div>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ state.error }}
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-white/5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Employee</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Dates</th>
                            <th class="px-4 py-3">Days</th>
                            <th class="px-4 py-3">Reason</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <tr v-if="state.loading"><td colspan="6" class="px-4 py-10 text-center text-slate-500">Loading…</td></tr>
                        <tr v-else-if="!state.rows.length"><td colspan="6" class="px-4 py-10 text-center text-slate-500">No pending leave requests.</td></tr>
                        <tr v-for="row in state.rows" :key="row.id">
                            <td class="px-4 py-3">
                                <span class="font-medium text-white">{{ row.employee?.full_name ?? '—' }}</span>
                                <span class="ml-2 font-mono text-xs text-slate-500">{{ row.employee?.employee_code }}</span>
                            </td>
                            <td class="px-4 py-3">{{ row.leave_type?.name ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ row.start_date }} ? {{ row.end_date }}</td>
                            <td class="px-4 py-3">{{ row.total_days }}</td>
                            <td class="max-w-xs truncate px-4 py-3 text-slate-400">{{ row.reason || '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" class="mr-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-2 py-1 text-xs text-amber-200 hover:bg-amber-500/20 disabled:opacity-40" :disabled="state.actingId === row.id" @click="approve(row)">Approve</button>
                                <button type="button" class="rounded-lg border border-red-500/30 bg-red-500/10 px-2 py-1 text-xs text-red-200 hover:bg-red-500/20 disabled:opacity-40" :disabled="state.actingId === row.id" @click="reject(row)">Reject</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

