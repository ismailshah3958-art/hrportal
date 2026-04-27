<template>
    <div class="space-y-8">
        <section
            class="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-emerald-950/40 via-[#121820] to-[#0c1016] p-6 sm:p-8"
        >
            <div
                class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-emerald-500/20 blur-3xl"
            />
            <div class="relative">
                <h2 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">Welcome back</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-400 sm:text-base">
                    Maestro Technologies HR — employees, attendance, leave, and payroll will connect here step by step.
                </p>
            </div>
        </section>

        <section v-if="feed.length">
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Announcements</h3>
            <ul class="space-y-3">
                <li
                    v-for="a in feed"
                    :key="a.id"
                    class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3"
                >
                    <p class="text-sm font-semibold text-white">
                        <span v-if="a.is_pinned" class="mr-1 text-amber-400">*</span>{{ a.title }}
                    </p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-400">{{ a.excerpt }}</p>
                </li>
            </ul>
        </section>

        <div
            v-if="state.hiddenMetrics.length"
            class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-100"
        >
            Some dashboard metrics are hidden for your role: {{ state.hiddenMetrics.join(', ') }}.
        </div>

        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Snapshot</h3>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in stats"
                    :key="card.title"
                    class="group rounded-2xl border border-white/10 bg-white/[0.03] p-5 transition hover:border-emerald-500/30 hover:bg-white/[0.05]"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-400">{{ card.title }}</p>
                            <p class="mt-2 text-3xl font-semibold tabular-nums text-white">{{ card.value }}</p>
                        </div>
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-400 [&>svg]:block"
                            v-html="card.icon"
                        ></span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">{{ card.hint }}</p>
                </article>
            </div>
        </section>

        <section>
            <h3 class="mb-4 text-sm font-semibold uppercase tracking-wider text-slate-500">Next modules</h3>
            <div class="grid gap-4 md:grid-cols-2">
                <button
                    type="button"
                    class="rounded-2xl border border-dashed border-emerald-500/30 bg-emerald-500/5 p-5 text-left text-sm text-slate-400 transition hover:border-emerald-500/50 hover:bg-emerald-500/10"
                    @click="router.push('/employees')"
                >
                    <p class="font-medium text-emerald-200/90">Employee &amp; org</p>
                    <p class="mt-2 leading-relaxed">
                        Employee list and create are live; departments and designations load from the database.
                    </p>
                    <span class="mt-3 inline-flex text-xs font-semibold text-emerald-400">Open employees →</span>
                </button>
                <button
                    type="button"
                    class="rounded-2xl border border-dashed border-white/15 bg-white/[0.02] p-5 text-left text-sm text-slate-400 transition hover:border-emerald-500/30 hover:bg-white/[0.04]"
                    @click="router.push('/attendance')"
                >
                    <p class="font-medium text-slate-300">Attendance &amp; leave</p>
                    <p class="mt-2 leading-relaxed">
                        Open an employee to see their full month, day by day (add / edit / delete), plus leave workflow.
                    </p>
                    <span class="mt-3 inline-flex text-xs font-semibold text-emerald-400">Open attendance →</span>
                </button>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const feed = reactive([]);
const state = reactive({
    loading: true,
    hiddenMetrics: [],
    metrics: {
        employees: 0,
        onLeaveToday: 0,
        pendingApprovals: 0,
        openPositions: 0,
    },
});

async function safeGet(url, label) {
    try {
        const { data } = await window.axios.get(url);
        return { ok: true, data };
    } catch (e) {
        if (e?.response?.status === 403) {
            state.hiddenMetrics.push(label);
            return { ok: false, data: null };
        }
        throw e;
    }
}

async function fetchEmployeeCount() {
    const first = await safeGet('/api/employees?per_page=5&page=1', 'employees');
    if (!first.ok) return 0;
    return Number(first.data?.meta?.total ?? (first.data?.data ?? []).length);
}

async function fetchOnLeaveToday() {
    const pending = await safeGet('/api/leave-requests/pending', 'leave approvals');
    if (!pending.ok) return 0;
    const rows = pending.data?.data ?? [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return rows.filter((r) => {
        const start = new Date(r.start_date);
        const end = new Date(r.end_date);
        start.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);
        return r.status === 'pending' && start <= today && today <= end;
    }).length;
}

onMounted(async () => {
    try {
        const [feedRes, employeeCount, onLeaveToday, pendingLeaves, jobPositions] = await Promise.all([
            safeGet('/api/announcements/feed', 'announcements'),
            fetchEmployeeCount(),
            fetchOnLeaveToday(),
            safeGet('/api/leave-requests/pending', 'leave approvals'),
            safeGet('/api/job-positions', 'recruitment'),
        ]);

        if (feedRes.ok) {
            const rows = feedRes.data?.data ?? [];
            feed.splice(0, feed.length, ...rows);
        }

        state.metrics.employees = employeeCount;
        state.metrics.onLeaveToday = onLeaveToday;
        state.metrics.pendingApprovals = pendingLeaves.ok ? (pendingLeaves.data?.data ?? []).length : 0;
        if (jobPositions.ok) {
            state.metrics.openPositions = (jobPositions.data?.data ?? []).filter((r) => r.status === 'open').length;
        }
    } catch {
        /* ignore */
    } finally {
        state.loading = false;
    }
});
const stats = computed(() => [
    {
        title: 'Employees',
        value: state.loading ? '…' : state.metrics.employees,
        hint: 'Total employee records',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.433-2.554M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>',
    },
    {
        title: 'On leave today',
        value: state.loading ? '…' : state.metrics.onLeaveToday,
        hint: 'Pending requests covering today',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5a2.25 2.25 0 002.25-2.25m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5a2.25 2.25 0 012.25 2.25v7.5" /></svg>',
    },
    {
        title: 'Pending approvals',
        value: state.loading ? '…' : state.metrics.pendingApprovals,
        hint: 'Leave requests awaiting action',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    },
    {
        title: 'Open positions',
        value: state.loading ? '…' : state.metrics.openPositions,
        hint: 'Recruitment pipeline',
        icon: '<svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-1.98-1.75-2.146a53.111 53.111 0 00-3.273-.424m0 0a48.098 48.098 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>',
    },
]);
</script>
