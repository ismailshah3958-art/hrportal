<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const route = useRoute();
const mobileNavOpen = ref(false);
const clockText = ref('');

const session = reactive({ loaded: false, flags: {}, employee: null });

const isEmployeeLayout = computed(() => session.loaded && !session.flags.hr_dashboard);

const employeeTopLinks = computed(() => {
    if (!isEmployeeLayout.value) {
        return [];
    }
    const list = [{ to: '/', label: 'Home' }];
    if (session.flags.ess_attendance_view && session.employee) {
        list.push({ to: '/my/attendance', label: 'My attendance' });
    }
    if (session.flags.ess_leave_apply && session.employee) {
        list.push({ to: '/my/leave', label: 'My leave' });
    }
    if (session.flags.ess_payslip_view && session.employee) {
        list.push({ to: '/my/payslips', label: 'My payslips' });
    }
    if (session.flags.leave_approver) {
        list.push({ to: '/leave-approvals', label: 'Leave approvals' });
    }
    return list;
});

const adminSidebarLinks = computed(() => {
    if (!session.loaded || !session.flags.hr_dashboard) {
        return [];
    }
    const list = [];
    list.push({ to: '/', label: 'Overview' });
    if (session.flags.hr_dashboard || session.flags.hr_employees_manage) {
        list.push({ to: '/employees', label: 'Employees' });
    }
    if (session.flags.hr_dashboard || session.flags.hr_attendance_manage) {
        list.push({ to: '/attendance', label: 'Attendance' });
    }
    if (session.flags.hr_payroll_manage) {
        list.push({ to: '/payroll', label: 'Payroll' });
    }
    if (session.flags.hr_dashboard || session.flags.hr_recruitment_manage) {
        list.push({ to: '/recruitment', label: 'Recruitment' });
    }
    if (session.flags.hr_dashboard) {
        list.push({ to: '/reports', label: 'Reports' });
    }
    if (session.flags.ess_attendance_view && session.employee) {
        list.push({ to: '/my/attendance', label: 'My attendance' });
    }
    if (session.flags.ess_leave_apply && session.employee) {
        list.push({ to: '/my/leave', label: 'My leave' });
    }
    if (session.flags.ess_payslip_view && session.employee) {
        list.push({ to: '/my/payslips', label: 'My payslips' });
    }
    if (session.flags.leave_approver) {
        list.push({ to: '/leave-approvals', label: 'Leave approvals' });
    }
    return list;
});

let clockTimer = null;

function isActive(path) {
    if (path === '/') return route.path === '/';
    return route.path === path || route.path.startsWith(path + '/');
}

function tickClock() {
    clockText.value = new Date().toLocaleString(undefined, {
        weekday: 'short',
        dateStyle: 'medium',
        timeStyle: 'medium',
    });
}

const pageTitle = computed(() => {
    if (route.path.startsWith('/employees')) return 'Employees';
    if (route.path.startsWith('/attendance')) return 'Attendance';
    if (route.path.startsWith('/payroll')) return 'Payroll';
    if (route.path.startsWith('/recruitment')) return 'Recruitment';
    if (route.path.startsWith('/announcements')) return 'Post announcements';
    if (route.path === '/my/announcements') return 'Home';
    if (route.path === '/') return session.flags?.hr_dashboard ? 'Overview' : 'Home';
    if (route.path === '/my/attendance') return 'My attendance';
    if (route.path === '/my/leave') return 'My leave';
    if (route.path === '/my/payslips') return 'My payslips';
    if (route.path === '/leave-approvals') return 'Leave approvals';
    if (route.path.startsWith('/reports')) return 'Reports';
    return 'Overview';
});

onMounted(async () => {
    try {
        const { data } = await window.axios.get('/api/me');
        session.flags = data.flags ?? {};
        session.employee = data.employee ?? null;
    } catch {
        session.flags = { hr_dashboard: true };
    } finally {
        session.loaded = true;
    }
    tickClock();
    clockTimer = setInterval(tickClock, 1000);
});

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#0f1419] text-slate-200">
        <div v-if="!session.loaded" class="px-4 py-8 text-center text-sm text-slate-500">Loading…</div>

        <!-- Employee: top bar + full-width content (admin color theme) -->
        <template v-else-if="isEmployeeLayout">
            <header class="sticky top-0 z-50 border-b border-white/10 bg-[#0f1419]">
                <div class="flex w-full items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/5 text-xs font-bold text-slate-200 sm:h-10 sm:w-10">
                            <img
                                v-if="session.employee?.profile_photo_url"
                                :src="session.employee.profile_photo_url"
                                alt=""
                                class="h-full w-full object-cover"
                            >
                            <span v-else>{{ (session.employee?.full_name || 'U').slice(0, 2).toUpperCase() }}</span>
                        </div>
                        <span class="shrink-0 text-sm font-semibold tracking-tight text-white sm:text-base">HR Portal</span>
                        <nav class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex">
                            <RouterLink
                                v-for="l in employeeTopLinks"
                                :key="l.to + l.label"
                                :to="l.to"
                                class="shrink-0 rounded-lg px-3 py-2 text-xs font-medium sm:text-sm"
                                :class="isActive(l.to) ? 'bg-emerald-600/20 text-emerald-300' : 'text-slate-300 hover:bg-white/5'"
                            >
                                {{ l.label }}
                            </RouterLink>
                        </nav>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <time class="hidden max-w-[16rem] truncate text-[10px] font-medium tabular-nums text-slate-400 lg:block" :title="clockText">{{ clockText }}</time>
                        <form method="post" action="/logout" class="inline">
                            <input type="hidden" name="_token" :value="csrf">
                            <button
                                type="submit"
                                class="rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/5 sm:px-4 sm:text-sm"
                            >
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
                <div class="flex gap-1 overflow-x-auto border-t border-white/10 px-2 py-2.5 md:hidden">
                    <RouterLink
                        v-for="l in employeeTopLinks"
                        :key="'m-' + l.to"
                        :to="l.to"
                        class="shrink-0 rounded-lg px-3 py-2 text-xs font-medium"
                        :class="isActive(l.to) ? 'bg-emerald-600/20 text-emerald-300' : 'text-slate-300 hover:bg-white/5'"
                    >
                        {{ l.label }}
                    </RouterLink>
                </div>
            </header>

            <div class="border-b border-white/10 bg-[#0c1016] px-3 py-1.5 text-center md:hidden">
                <time class="text-[10px] font-medium tabular-nums text-slate-500">{{ clockText }}</time>
            </div>

            <main class="w-full px-4 py-4 sm:px-6 sm:py-6">
                <RouterView />
            </main>
        </template>

        <!-- Admin / HR: sidebar layout (unchanged behaviour) -->
        <template v-else>
            <div v-show="mobileNavOpen" class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="mobileNavOpen = false"></div>

            <aside class="fixed inset-y-0 left-0 z-50 w-[260px] border-r border-white/10 bg-[#0c1016] p-4 transition-transform lg:translate-x-0" :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
                <p class="mb-4 text-lg font-semibold text-white">HR Portal</p>
                <nav class="space-y-1">
                    <RouterLink
                        v-for="l in adminSidebarLinks"
                        :key="l.to + l.label"
                        :to="l.to"
                        class="block rounded-lg px-3 py-2 text-sm"
                        :class="isActive(l.to) ? 'bg-emerald-600/20 text-emerald-300' : 'text-slate-300 hover:bg-white/5'"
                        @click="mobileNavOpen = false"
                    >
                        {{ l.label }}
                    </RouterLink>
                </nav>

                <form method="post" action="/logout" class="mt-6 border-t border-white/10 pt-4">
                    <input type="hidden" name="_token" :value="csrf">
                    <button type="submit" class="w-full rounded-lg border border-white/10 px-3 py-2 text-sm text-slate-300 hover:bg-white/5">Log out</button>
                </form>
            </aside>

            <div class="lg:pl-[260px]">
                <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-white/10 bg-[#0f1419] px-4">
                    <button class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-white/10 lg:hidden" @click="mobileNavOpen = true">Menu</button>
                    <h1 class="text-sm font-semibold text-white">{{ pageTitle }}</h1>
                </header>

                <main class="w-full p-4 sm:p-6">
                    <RouterView />
                </main>
            </div>
        </template>
    </div>
</template>
