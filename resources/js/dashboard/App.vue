<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const route = useRoute();
const clockText = ref('');
const isDarkMode = ref(true);
const THEME_STORAGE_KEY = 'hrportal-dashboard-theme';

const session = reactive({ loaded: false, flags: {}, employee: null, userName: '' });

/** HR + employees: same top-nav, full-width shell (no sidebar) */
const useFixedShell = computed(() => session.loaded);

const employeeTopLinks = computed(() => {
    if (!session.loaded || session.flags.hr_dashboard) {
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
    if (session.flags.hr_announcements_manage) {
        list.push({ to: '/announcements', label: 'Announcements' });
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

const topNavLinks = computed(() =>
    session.flags.hr_dashboard ? adminSidebarLinks.value : employeeTopLinks.value
);

const avatarInitials = computed(() => {
    const n = session.employee?.full_name || session.userName || 'U';
    const p = String(n).trim().split(/\s+/).filter(Boolean);
    if (p.length >= 2) {
        return (p[0][0] + p[1][0]).toUpperCase();
    }
    return String(n).slice(0, 2).toUpperCase();
});

const shellClass = computed(() =>
    isDarkMode.value ? 'bg-[#0f1419] text-slate-200' : 'bg-slate-100 text-slate-900'
);
const themeClass = computed(() => (isDarkMode.value ? 'theme-dark' : 'theme-light'));

const headerClass = computed(() =>
    isDarkMode.value ? 'border-b border-white/10 bg-[#0f1419]' : 'border-b border-slate-200 bg-white'
);

const avatarClass = computed(() =>
    isDarkMode.value
        ? 'border border-white/10 bg-white/5 text-slate-200'
        : 'border border-slate-200 bg-slate-100 text-slate-700'
);

const navLinkClass = computed(() =>
    isDarkMode.value ? 'text-slate-300 hover:bg-white/5' : 'text-slate-700 hover:bg-slate-100'
);

const activeNavLinkClass = computed(() =>
    isDarkMode.value ? 'bg-emerald-600/20 text-emerald-300' : 'bg-slate-900 text-white'
);

const brandClass = computed(() => (isDarkMode.value ? 'text-white' : 'text-slate-900'));
const subtitleClass = computed(() => (isDarkMode.value ? 'text-slate-500' : 'text-slate-500'));
const clockClass = computed(() => (isDarkMode.value ? 'text-slate-400' : 'text-slate-500'));

const buttonClass = computed(() =>
    isDarkMode.value
        ? 'rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/5 sm:px-4 sm:text-sm'
        : 'rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100 sm:px-4 sm:text-sm'
);

const mobileClockBarClass = computed(() =>
    isDarkMode.value ? 'border-b border-white/10 bg-[#0c1016]' : 'border-b border-slate-200 bg-slate-50'
);

function applyTheme() {
    localStorage.setItem(THEME_STORAGE_KEY, isDarkMode.value ? 'dark' : 'light');
    document.documentElement.classList.toggle('dark', isDarkMode.value);
}

function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    applyTheme();
}

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
    const storedTheme = localStorage.getItem(THEME_STORAGE_KEY);
    if (storedTheme === 'light') {
        isDarkMode.value = false;
    } else if (storedTheme === 'dark') {
        isDarkMode.value = true;
    } else {
        isDarkMode.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyTheme();

    try {
        const { data } = await window.axios.get('/api/me');
        session.flags = data.flags ?? {};
        session.employee = data.employee ?? null;
        session.userName = data.user?.name ?? '';
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
    <div
        :class="[themeClass, shellClass, useFixedShell ? 'flex h-dvh max-h-dvh flex-col overflow-hidden' : 'min-h-screen']"
    >
        <div v-if="!session.loaded" class="px-4 py-8 text-center text-sm text-slate-500">Loading…</div>

        <!-- Top bar + full-width main (HR + employees, no sidebar) -->
        <template v-else>
            <header class="z-50 shrink-0" :class="headerClass">
                <div class="flex w-full items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-xs font-bold sm:h-10 sm:w-10" :class="avatarClass">
                            <img
                                v-if="session.employee?.profile_photo_url"
                                :src="session.employee.profile_photo_url"
                                alt=""
                                class="h-full w-full object-cover"
                            >
                            <span v-else>{{ avatarInitials }}</span>
                        </div>
                        <div class="hidden min-w-0 sm:block">
                            <span class="text-sm font-semibold tracking-tight sm:text-base" :class="brandClass">HR Portal</span>
                            <p v-if="session.flags.hr_dashboard" class="text-[10px]" :class="subtitleClass">{{ pageTitle }}</p>
                        </div>
                        <nav class="hidden min-w-0 flex-1 items-center gap-1 overflow-x-auto md:flex">
                            <RouterLink
                                v-for="l in topNavLinks"
                                :key="l.to + l.label"
                                :to="l.to"
                                class="shrink-0 rounded-lg px-3 py-2 text-xs font-medium sm:text-sm"
                                :class="isActive(l.to) ? activeNavLinkClass : navLinkClass"
                            >
                                {{ l.label }}
                            </RouterLink>
                        </nav>
                    </div>
                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-slate-600 transition hover:scale-[1.02]"
                            :class="isDarkMode ? 'border-white/10 text-slate-300 hover:bg-white/5' : 'border-slate-300 text-slate-700 hover:bg-slate-100'"
                            :title="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                            :aria-label="isDarkMode ? 'Switch to light mode' : 'Switch to dark mode'"
                            @click="toggleTheme"
                        >
                            <svg v-if="isDarkMode" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M21.752 15.002A9 9 0 0 1 8.998 2.248a.75.75 0 0 0-.884-1.166A10.5 10.5 0 1 0 22.918 15.886a.75.75 0 0 0-1.166-.884Z" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V3a.75.75 0 0 1 .75-.75Zm0 16.5a.75.75 0 0 1 .75.75V21a.75.75 0 0 1-1.5 0v-1.5a.75.75 0 0 1 .75-.75Zm9-6.75a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 1 .75.75Zm-16.5 0a.75.75 0 0 1-.75.75H2.25a.75.75 0 0 1 0-1.5h1.5a.75.75 0 0 1 .75.75Zm12.364 6.864a.75.75 0 0 1 0 1.06l-1.06 1.061a.75.75 0 1 1-1.061-1.06l1.061-1.061a.75.75 0 0 1 1.06 0Zm-9.192-9.193a.75.75 0 0 1 0 1.061L6.61 11.793a.75.75 0 1 1-1.06-1.061L6.61 9.67a.75.75 0 0 1 1.061 0Zm9.193 1.061a.75.75 0 0 1-1.06 0l-1.061-1.06a.75.75 0 0 1 1.06-1.062l1.061 1.061a.75.75 0 0 1 0 1.061Zm-9.193 9.193a.75.75 0 0 1-1.061 0L5.55 18.864a.75.75 0 0 1 1.06-1.06l1.062 1.06a.75.75 0 0 1 0 1.061ZM12 7.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" />
                            </svg>
                        </button>
                        <time class="hidden max-w-[16rem] truncate text-[10px] font-medium tabular-nums lg:block" :class="clockClass" :title="clockText">{{ clockText }}</time>
                        <form method="post" action="/logout" class="inline">
                            <input type="hidden" name="_token" :value="csrf">
                            <button
                                type="submit"
                                :class="buttonClass"
                            >
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
                <div class="flex gap-1 overflow-x-auto border-t border-white/10 px-2 py-2.5 md:hidden">
                    <RouterLink
                        v-for="l in topNavLinks"
                        :key="'m-' + l.to + l.label"
                        :to="l.to"
                        class="shrink-0 rounded-lg px-3 py-2 text-xs font-medium"
                        :class="isActive(l.to) ? activeNavLinkClass : navLinkClass"
                    >
                        {{ l.label }}
                    </RouterLink>
                </div>
            </header>

            <div class="shrink-0 px-3 py-1.5 text-center md:hidden" :class="mobileClockBarClass">
                <time class="text-[10px] font-medium tabular-nums" :class="clockClass">{{ clockText }}</time>
            </div>

            <main class="flex min-h-0 w-full flex-1 flex-col overflow-hidden px-4 pb-4 pt-4 sm:px-6 sm:pb-6 sm:pt-6">
                <RouterView v-slot="{ Component }">
                    <component
                        :is="Component"
                        class="flex min-h-0 w-full min-w-0 flex-1 flex-col overflow-hidden"
                    />
                </RouterView>
            </main>
        </template>
    </div>
</template>
