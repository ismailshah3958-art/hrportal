<script setup>
import { computed, onMounted, reactive } from 'vue';

const state = reactive({
    loading: true,
    error: '',
    forbidden: [],
    metrics: {
        totalEmployees: 0,
        activeEmployees: 0,
        onLeaveEmployees: 0,
        exitedEmployees: 0,
        pendingLeaves: 0,
        openPositions: 0,
        totalOpenings: 0,
        payrollRuns: 0,
        latestPayrollPeriod: '—',
    },
    recentEmployees: [],
});

function fmtMonth(year, month) {
    if (!year || !month) return '—';
    try {
        return new Date(Number(year), Number(month) - 1, 1).toLocaleString(undefined, {
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return `${month}/${year}`;
    }
}

function fmtDate(val) {
    if (!val) return '—';
    try {
        return new Date(val).toLocaleDateString(undefined, { dateStyle: 'medium' });
    } catch {
        return String(val);
    }
}

async function safeGet(url, permissionLabel) {
    try {
        const { data } = await window.axios.get(url);
        return { ok: true, data };
    } catch (e) {
        if (e?.response?.status === 403) {
            state.forbidden.push(permissionLabel);
            return { ok: false, data: null };
        }
        throw e;
    }
}

async function fetchEmployeesAllPages() {
    const first = await safeGet('/api/employees?per_page=50&page=1', 'employees');
    if (!first.ok) return [];

    const all = [...(first.data?.data ?? [])];
    const lastPage = Number(first.data?.meta?.last_page ?? 1);
    const maxPages = Math.min(lastPage, 20);

    for (let p = 2; p <= maxPages; p++) {
        const page = await safeGet(`/api/employees?per_page=50&page=${p}`, 'employees');
        if (!page.ok) break;
        all.push(...(page.data?.data ?? []));
    }
    return all;
}

const cardRows = computed(() => [
    { title: 'Total employees', value: state.metrics.totalEmployees, hint: 'Current records' },
    { title: 'Active now', value: state.metrics.activeEmployees, hint: `${state.metrics.onLeaveEmployees} on leave` },
    { title: 'Exited', value: state.metrics.exitedEmployees, hint: 'Resigned + terminated' },
    { title: 'Pending leave approvals', value: state.metrics.pendingLeaves, hint: 'Awaiting action' },
    { title: 'Open positions', value: state.metrics.openPositions, hint: `${state.metrics.totalOpenings} openings` },
    { title: 'Payroll runs', value: state.metrics.payrollRuns, hint: `Latest: ${state.metrics.latestPayrollPeriod}` },
]);

async function load() {
    state.loading = true;
    state.error = '';
    state.forbidden = [];
    try {
        const employees = await fetchEmployeesAllPages();
        state.metrics.totalEmployees = employees.length;
        state.metrics.activeEmployees = employees.filter((e) => e.status === 'active').length;
        state.metrics.onLeaveEmployees = employees.filter((e) => e.status === 'on_leave').length;
        const resigned = employees.filter((e) => e.status === 'resigned').length;
        const terminated = employees.filter((e) => e.status === 'terminated').length;
        state.metrics.exitedEmployees = resigned + terminated;
        state.recentEmployees = employees
            .slice()
            .sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')))
            .slice(0, 6);

        const [leaves, positions, payroll] = await Promise.all([
            safeGet('/api/leave-requests/pending', 'leave approvals'),
            safeGet('/api/job-positions', 'recruitment'),
            safeGet('/api/payroll/runs', 'payroll'),
        ]);

        if (leaves.ok) {
            state.metrics.pendingLeaves = (leaves.data?.data ?? []).length;
        }

        if (positions.ok) {
            const rows = positions.data?.data ?? [];
            state.metrics.openPositions = rows.filter((r) => r.status === 'open').length;
            state.metrics.totalOpenings = rows
                .filter((r) => r.status === 'open')
                .reduce((sum, r) => sum + Number(r.openings ?? 0), 0);
        }

        if (payroll.ok) {
            const runs = payroll.data?.data ?? [];
            state.metrics.payrollRuns = Number(payroll.data?.meta?.total ?? runs.length);
            const latest = runs[0];
            state.metrics.latestPayrollPeriod = latest
                ? fmtMonth(latest.period_year, latest.period_month)
                : '—';
        }
    } catch (e) {
        state.error = e?.response?.data?.message ?? 'Could not load report metrics.';
    } finally {
        state.loading = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-8">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold text-white sm:text-2xl">Reports</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-400">
                        Live HR snapshot based on employees, leave approvals, payroll runs, and recruitment pipeline.
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg border border-white/10 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-white/5"
                    @click="load"
                >
                    Refresh
                </button>
            </div>
        </section>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
            {{ state.error }}
        </div>

        <div
            v-if="state.forbidden.length"
            class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-100"
        >
            Some metrics are hidden because your role cannot access: {{ state.forbidden.join(', ') }}.
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="row in cardRows"
                :key="row.title"
                class="rounded-2xl border border-white/10 bg-white/[0.02] p-5"
            >
                <p class="text-xs uppercase tracking-wider text-slate-500">{{ row.title }}</p>
                <p class="mt-2 text-2xl font-semibold text-white">
                    <span v-if="state.loading">…</span>
                    <span v-else>{{ row.value }}</span>
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ row.hint }}</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                Recently added employees
            </h3>
            <div class="p-6">
                <div v-if="state.loading" class="text-sm text-slate-500">Loading employees…</div>
                <div v-else-if="!state.recentEmployees.length" class="text-sm text-slate-500">No data available.</div>
                <div v-else class="divide-y divide-white/5">
                    <div v-for="e in state.recentEmployees" :key="e.id" class="flex items-center justify-between py-3">
                        <div>
                            <p class="text-sm font-medium text-white">{{ e.full_name || '—' }}</p>
                            <p class="text-xs text-slate-500">
                                {{ e.employee_code || '—' }} · {{ String(e.status || '—').replaceAll('_', ' ') }}
                            </p>
                        </div>
                        <p class="text-xs text-slate-500">{{ fmtDate(e.created_at) }}</p>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
