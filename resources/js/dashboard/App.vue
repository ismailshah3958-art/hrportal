<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const route = useRoute();
const mobileNavOpen = ref(false);

const session = reactive({ loaded: false, flags: {}, employee: null });

const links = computed(() => {
    const list = [];

    if (session.loaded) {
        list.push({ to: '/my/announcements', label: 'Announcements' });
    }

    if (session.flags.hr_dashboard) {
        list.push({ to: '/', label: 'Overview' });
    }

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

    if (!list.length) {
        list.push({ to: '/', label: 'Overview' });
    }

    return list;
});

function isActive(path) {
    if (path === '/') return route.path === '/';
    return route.path === path || route.path.startsWith(path + '/');
}

const pageTitle = computed(() => {
    if (route.path.startsWith('/employees')) return 'Employees';
    if (route.path.startsWith('/attendance')) return 'Attendance';
    if (route.path.startsWith('/payroll')) return 'Payroll';
    if (route.path.startsWith('/recruitment')) return 'Recruitment';
    if (route.path.startsWith('/announcements')) return 'Post announcements';
    if (route.path === '/my/announcements') return 'Announcements';
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
});
</script>

<template>
    <div class="min-h-screen bg-[#0f1419] text-slate-200">
        <div v-show="mobileNavOpen" class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="mobileNavOpen = false"></div>

        <aside class="fixed inset-y-0 left-0 z-50 w-[260px] border-r border-white/10 bg-[#0c1016] p-4 transition-transform lg:translate-x-0" :class="mobileNavOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <p class="mb-4 text-lg font-semibold text-white">HR Portal</p>
            <nav class="space-y-1">
                <p v-if="!session.loaded" class="px-3 py-2 text-sm text-slate-500">Loading...</p>
                <RouterLink v-for="l in links" v-else :key="l.to" :to="l.to" class="block rounded-lg px-3 py-2 text-sm" :class="isActive(l.to) ? 'bg-emerald-600/20 text-emerald-300' : 'text-slate-300 hover:bg-white/5'" @click="mobileNavOpen = false">
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

            <main class="p-4 sm:p-6">
                <div class="mx-auto max-w-6xl">
                    <RouterView />
                </div>
            </main>
        </div>
    </div>
</template>
