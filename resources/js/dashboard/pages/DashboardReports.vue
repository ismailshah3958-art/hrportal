<template>
    <div class="space-y-8">
        <section class="rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-8">
            <h2 class="text-xl font-semibold text-white sm:text-2xl">Reports</h2>
            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-slate-400">
                Attendance, payroll, and employee analytics will be added here with exports and filters. This page is
                layout-only for now.
            </p>
        </section>

        <div class="grid gap-4 md:grid-cols-2">
            <button
                v-for="row in rows"
                :key="row.title"
                type="button"
                class="group flex flex-col rounded-2xl border border-white/10 bg-white/[0.02] p-5 text-left transition hover:border-emerald-500/25 hover:bg-white/[0.04]"
            >
                <span class="text-sm font-semibold text-white">{{ row.title }}</span>
                <span class="mt-1 text-xs text-slate-500">{{ row.desc }}</span>
                <span
                    class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-emerald-400/90 opacity-80 group-hover:opacity-100"
                >
                    Coming soon
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
const rows = [
    { title: 'Attendance summary', desc: 'Daily / monthly, late & early leave' },
    { title: 'Payroll register', desc: 'Runs, deductions, net pay' },
    { title: 'Leave utilization', desc: 'By type, department, employee' },
    { title: 'Headcount & turnover', desc: 'Joiners, exits, active count' },
];
</script>
