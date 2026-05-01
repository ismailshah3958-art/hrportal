<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const route = useRoute();
const router = useRouter();
const clockText = ref('');
const isDarkMode = ref(true);
const THEME_STORAGE_KEY = 'hrportal-dashboard-theme';
const companyLogo = '/logo-maestrotech.svg';

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
    if (session.flags.hr_dashboard) {
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

const shellClass = computed(() =>
    isDarkMode.value ? 'bg-[#0f1419] text-slate-200' : 'bg-slate-100 text-slate-900'
);
const themeClass = computed(() => (isDarkMode.value ? 'theme-dark' : 'theme-light'));

const headerClass = computed(() =>
    isDarkMode.value ? 'border-b border-white/10 bg-[#0f1419]' : 'border-b border-slate-200 bg-white'
);

const navLinkClass = computed(() =>
    isDarkMode.value ? 'text-slate-300 hover:bg-white/5' : 'text-slate-700 hover:bg-slate-100'
);

const activeNavLinkClass = computed(() =>
    isDarkMode.value ? 'bg-[rgb(249_184_79_/0.18)] text-amber-200' : 'bg-slate-900 text-white'
);

const clockClass = computed(() => (isDarkMode.value ? 'text-slate-400' : 'text-slate-500'));

const buttonClass = computed(() =>
    isDarkMode.value
        ? 'rounded-lg border border-white/10 px-3 py-1.5 text-xs text-slate-300 hover:bg-white/5 sm:px-4 sm:text-sm'
        : 'rounded-lg border border-slate-300 px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100 sm:px-4 sm:text-sm'
);

const mobileClockBarClass = computed(() =>
    isDarkMode.value ? 'border-b border-white/10 bg-[#0c1016]' : 'border-b border-slate-200 bg-slate-50'
);

const notificationState = reactive({
    open: false,
    loading: false,
    unreadCount: 0,
    rows: [],
});

function applyTheme() {
    localStorage.setItem(THEME_STORAGE_KEY, isDarkMode.value ? 'dark' : 'light');
    document.documentElement.classList.toggle('dark', isDarkMode.value);
}

function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    applyTheme();
}

let clockTimer = null;
let notificationsTimer = null;
let previousRootFontSize = '';

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

function applyResponsiveRootScale() {
    const w = window.innerWidth || 0;
    let size = 16;
    if (w >= 1900) {
        size = 20;
    } else if (w >= 1600) {
        size = 19;
    } else if (w >= 1366) {
        size = 18;
    } else if (w >= 1200) {
        size = 17;
    }
    document.documentElement.style.fontSize = `${size}px`;
}

async function fetchNotifications() {
    notificationState.loading = true;
    try {
        const { data } = await window.axios.get('/api/notifications');
        notificationState.unreadCount = Number(data?.unread_count ?? 0);
        notificationState.rows = data?.data ?? [];
    } catch {
        notificationState.rows = [];
    } finally {
        notificationState.loading = false;
    }
}

function formatNotificationTime(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' });
    } catch {
        return '';
    }
}

async function markNotificationRead(id) {
    try {
        await window.axios.post(`/api/notifications/${id}/read`);
        const row = notificationState.rows.find((r) => r.id === id);
        if (row && !row.read_at) {
            row.read_at = new Date().toISOString();
            notificationState.unreadCount = Math.max(0, notificationState.unreadCount - 1);
        }
    } catch {
        /* noop */
    }
}

async function onNotificationClick(n) {
    if (!n.read_at) {
        await markNotificationRead(n.id);
    }
    notificationState.open = false;
    if (n.link) {
        router.push(n.link);
    }
}

async function toggleNotifications() {
    notificationState.open = !notificationState.open;
    if (notificationState.open) {
        await fetchNotifications();
    }
}

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
    previousRootFontSize = document.documentElement.style.fontSize || '';
    applyResponsiveRootScale();
    window.addEventListener('resize', applyResponsiveRootScale);

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
    await fetchNotifications();
    tickClock();
    clockTimer = setInterval(tickClock, 1000);
    notificationsTimer = setInterval(fetchNotifications, 30000);
});

watch(
    () => route.fullPath,
    () => {
        notificationState.open = false;
    }
);

onUnmounted(() => {
    if (clockTimer) {
        clearInterval(clockTimer);
    }
    if (notificationsTimer) {
        clearInterval(notificationsTimer);
    }
    window.removeEventListener('resize', applyResponsiveRootScale);
    document.documentElement.style.fontSize = previousRootFontSize;
});
</script>

<template>
    <div
        :class="['dashboard-adaptive', themeClass, shellClass, useFixedShell ? 'flex h-dvh max-h-dvh flex-col overflow-hidden' : 'min-h-screen']"
    >
        <div v-if="!session.loaded" class="px-4 py-8 text-center text-sm text-slate-500">Loading…</div>

        <!-- Top bar + full-width main (HR + employees, no sidebar) -->
        <template v-else>
            <header class="z-50 shrink-0" :class="headerClass">
                <div class="flex w-full items-center justify-between gap-3 px-4 py-3 sm:px-6">
                    <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                        <RouterLink
                            to="/"
                            class="flex shrink-0 items-center outline-none ring-offset-2 focus-visible:ring-2 focus-visible:ring-amber-400/80"
                            :class="isDarkMode ? 'ring-offset-[#0f1419]' : 'ring-offset-white'"
                            aria-label="Home"
                        >
                            <img
                                :src="companyLogo"
                                alt="MaestroTech"
                                class="h-8 w-auto shrink-0 sm:h-10"
                                :class="isDarkMode ? 'brightness-0 invert' : ''"
                            >
                        </RouterLink>
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
                        <div class="relative">
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border text-slate-600 transition hover:scale-[1.02]"
                                :class="isDarkMode ? 'border-white/10 text-slate-300 hover:bg-white/5' : 'border-slate-300 text-slate-700 hover:bg-slate-100'"
                                title="Notifications"
                                aria-label="Notifications"
                                @click="toggleNotifications"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.25a6 6 0 0 0-6 6v3.764c0 .577-.174 1.14-.5 1.616l-1.27 1.857A1.125 1.125 0 0 0 5.157 17.25h13.686a1.125 1.125 0 0 0 .927-1.763l-1.27-1.857a2.875 2.875 0 0 1-.5-1.616V8.25a6 6 0 0 0-6-6ZM8.25 18.75a3.75 3.75 0 0 0 7.5 0h-7.5Z" />
                                </svg>
                            </button>
                            <span
                                v-if="notificationState.unreadCount > 0"
                                class="absolute -right-1 -top-1 inline-flex min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                            >
                                {{ notificationState.unreadCount > 99 ? '99+' : notificationState.unreadCount }}
                            </span>

                            <div
                                v-if="notificationState.open"
                                class="absolute right-0 z-[80] mt-2 w-80 overflow-hidden rounded-xl border border-white/10 bg-[#121820] shadow-2xl"
                            >
                                <div class="flex items-center justify-between border-b border-white/10 px-3 py-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notifications</p>
                                    <span class="text-[10px] text-slate-500">Unread: {{ notificationState.unreadCount }}</span>
                                </div>
                                <div v-if="notificationState.loading" class="px-3 py-4 text-xs text-slate-500">Loading…</div>
                                <ul v-else-if="notificationState.rows.length" class="max-h-80 overflow-y-auto">
                                    <li
                                        v-for="n in notificationState.rows"
                                        :key="n.id"
                                        class="border-b border-white/5 last:border-b-0"
                                    >
                                        <button
                                            type="button"
                                            class="w-full px-3 py-2 text-left transition hover:bg-white/[0.03]"
                                            :class="n.read_at ? 'opacity-75' : ''"
                                            @click="onNotificationClick(n)"
                                        >
                                            <p class="text-xs font-medium" :class="n.read_at ? 'text-slate-300' : 'text-white'">{{ n.title }}</p>
                                            <p class="mt-0.5 line-clamp-2 text-[11px] text-slate-400">{{ n.body }}</p>
                                            <p class="mt-1 text-[10px] text-slate-500">{{ formatNotificationTime(n.created_at) }}</p>
                                        </button>
                                    </li>
                                </ul>
                                <div v-else class="px-3 py-4 text-xs text-slate-500">No notifications.</div>
                            </div>
                        </div>

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
