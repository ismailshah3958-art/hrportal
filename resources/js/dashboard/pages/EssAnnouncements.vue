<script setup>
import { computed, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const state = reactive({
    rows: [],
    birthdays: [],
    birthdaysError: '',
    employee: null,
    loading: false,
    error: '',
    canManage: false,
    q: '',
    onlyPinned: false,
});

async function load() {
    state.loading = true;
    state.error = '';
    state.birthdaysError = '';
    try {
        const [feedRes, meRes, bRes] = await Promise.all([
            window.axios.get('/api/announcements/feed'),
            window.axios.get('/api/me'),
            window.axios.get('/api/company/birthdays-timeline').catch((e) => ({
                data: { data: [] },
                __birthdayErr: e.response?.data?.message ?? 'Could not load birthdays.',
            })),
        ]);
        state.rows = feedRes.data.data ?? [];
        state.employee = meRes.data?.employee ?? null;
        state.canManage = !!(meRes.data?.flags?.hr_announcements_manage);
        if (bRes.__birthdayErr) {
            state.birthdaysError = bRes.__birthdayErr;
            state.birthdays = [];
        } else {
            state.birthdays = bRes.data?.data ?? [];
        }
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load feed.';
        state.rows = [];
    } finally {
        state.loading = false;
    }
}

onMounted(load);

const filteredRows = computed(() => {
    const q = state.q.trim().toLowerCase();
    return state.rows.filter((row) => {
        if (state.onlyPinned && !row.is_pinned) {
            return false;
        }
        if (!q) return true;
        const title = String(row.title ?? '').toLowerCase();
        const body = String(row.body ?? '').toLowerCase();
        return title.includes(q) || body.includes(q);
    });
});

const todayBirthdays = computed(() => state.birthdays.filter((b) => b.is_today));
const soonBirthdays = computed(() =>
    state.birthdays.filter((b) => !b.is_today && b.days_until >= 1 && b.days_until <= 14)
);
const laterBirthdays = computed(() => state.birthdays.filter((b) => !b.is_today && b.days_until > 14));

function initials(name) {
    if (!name) return '?';
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return String(name).slice(0, 2).toUpperCase();
}

function formatShortDate(isoDate) {
    if (!isoDate) return '';
    try {
        return new Date(isoDate + 'T12:00:00').toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return isoDate;
    }
}

function formatPublished(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    } catch {
        return '';
    }
}
</script>

<template>
    <div class="mx-auto max-w-2xl space-y-8 pb-10">
        <!-- Cover strip (timeline feel) -->
        <section
            class="relative overflow-hidden rounded-2xl border border-white/10 bg-gradient-to-br from-violet-950/50 via-[#121820] to-[#0c1016] p-6 sm:p-8"
        >
            <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-violet-500/15 blur-3xl" />
            <div class="relative">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-300/90">Company feed</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-white sm:text-3xl">
                    {{ state.employee ? `Hi, ${state.employee.full_name}` : 'Team updates' }}
                </h2>
                <p class="mt-2 max-w-md text-sm leading-relaxed text-slate-400">
                    Birthdays across the company and the latest announcements — like a simple timeline.
                </p>
            </div>
        </section>

        <div v-if="state.canManage" class="flex flex-wrap justify-end gap-3">
            <button
                type="button"
                class="rounded-xl border border-white/15 px-4 py-2 text-sm text-slate-200 hover:bg-white/5"
                @click="router.push('/announcements')"
            >
                Post / manage
            </button>
        </div>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>
        <div v-if="state.birthdaysError" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-xs text-amber-100">{{ state.birthdaysError }}</div>

        <div v-if="state.loading" class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-slate-500">Loading feed…</div>

        <template v-else>
            <!-- Birthdays -->
            <section v-if="todayBirthdays.length" class="space-y-3">
                <h3 class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-amber-400/90">
                    <span aria-hidden="true">🎂</span> Today’s birthdays
                </h3>
                <ul class="space-y-3">
                    <li
                        v-for="b in todayBirthdays"
                        :key="'t-' + b.employee_id"
                        class="flex items-center gap-4 rounded-2xl border border-amber-500/25 bg-gradient-to-r from-amber-500/10 to-white/[0.02] p-4"
                        :class="b.is_me ? 'ring-2 ring-emerald-500/30' : ''"
                    >
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/15 bg-white/10 text-sm font-semibold text-slate-200"
                        >
                            <img v-if="b.profile_photo_url" :src="b.profile_photo_url" alt="" class="h-full w-full object-cover">
                            <span v-else>{{ initials(b.full_name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-white">
                                {{ b.full_name }}
                                <span v-if="b.is_me" class="ml-1 text-xs font-normal text-emerald-300">(You)</span>
                            </p>
                            <p class="text-xs text-slate-500">{{ b.designation_name || '—' }} · {{ b.department_name || '—' }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="text-sm font-semibold text-amber-200">Today</span>
                            <p class="text-[11px] text-slate-500">Turns {{ b.turns_age }}</p>
                        </div>
                    </li>
                </ul>
            </section>

            <section v-if="soonBirthdays.length" class="space-y-3">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Coming up</h3>
                <ul class="space-y-2">
                    <li
                        v-for="b in soonBirthdays"
                        :key="'s-' + b.employee_id"
                        class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-3"
                        :class="b.is_me ? 'ring-1 ring-emerald-500/25' : ''"
                    >
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/5 text-xs font-semibold text-slate-300">
                            <img v-if="b.profile_photo_url" :src="b.profile_photo_url" alt="" class="h-full w-full object-cover">
                            <span v-else>{{ initials(b.full_name) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-white">{{ b.full_name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ b.department_name || '—' }}</p>
                        </div>
                        <div class="shrink-0 text-right text-xs text-slate-400">
                            <span class="font-medium text-emerald-200/90">in {{ b.days_until }}d</span>
                            <p class="text-[10px] text-slate-600">{{ formatShortDate(b.next_birthday_on) }}</p>
                        </div>
                    </li>
                </ul>
            </section>

            <section v-if="laterBirthdays.length" class="space-y-2">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-600">Later this season</h3>
                <ul class="divide-y divide-white/5 rounded-2xl border border-white/10 bg-white/[0.02]">
                    <li v-for="b in laterBirthdays" :key="'l-' + b.employee_id" class="flex items-center gap-3 px-3 py-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/5 text-[10px] font-semibold text-slate-400">
                            <img v-if="b.profile_photo_url" :src="b.profile_photo_url" alt="" class="h-full w-full object-cover">
                            <span v-else>{{ initials(b.full_name) }}</span>
                        </div>
                        <span class="min-w-0 flex-1 truncate text-xs text-slate-300">{{ b.full_name }}</span>
                        <span class="shrink-0 text-[10px] text-slate-600">{{ formatShortDate(b.next_birthday_on) }}</span>
                    </li>
                </ul>
            </section>

            <!-- Filters -->
            <div class="sticky top-14 z-20 -mx-1 rounded-2xl border border-white/10 bg-[#0f1419]/95 px-3 py-3 backdrop-blur sm:static sm:bg-transparent sm:px-0 sm:py-0">
                <div class="grid gap-3 sm:grid-cols-4">
                    <input
                        v-model.trim="state.q"
                        type="search"
                        placeholder="Search posts…"
                        class="sm:col-span-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 text-sm text-white placeholder:text-slate-500"
                    >
                    <label class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-300">
                        <input v-model="state.onlyPinned" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/10 text-violet-500">
                        Pinned only
                    </label>
                </div>
            </div>

            <!-- Announcement “posts” -->
            <section class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Announcements</h3>

                <ul v-if="filteredRows.length" class="space-y-5">
                    <li
                        v-for="a in filteredRows"
                        :key="a.id"
                        class="overflow-hidden rounded-2xl border border-white/10 bg-[#121820] shadow-lg shadow-black/20"
                    >
                        <div
                            class="h-1 w-full bg-gradient-to-r from-violet-500/60 via-emerald-500/40 to-transparent"
                            :class="a.is_pinned ? 'from-amber-500/70' : ''"
                        />
                        <div class="flex gap-4 p-4 sm:p-5">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-gradient-to-br from-white/10 to-white/5 text-xs font-bold tracking-tight text-slate-300"
                                aria-hidden="true"
                            >
                                MT
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-white">Maestro Technologies</span>
                                    <span
                                        v-if="a.is_pinned"
                                        class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-200"
                                    >Pinned</span>
                                </div>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ formatPublished(a.published_at) }}</p>
                                <h4 class="mt-3 text-lg font-semibold leading-snug text-white">{{ a.title }}</h4>
                                <div class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-300">{{ a.body }}</div>
                            </div>
                        </div>
                    </li>
                </ul>

                <p v-else class="rounded-2xl border border-dashed border-white/10 px-6 py-12 text-center text-sm text-slate-500">
                    No posts match your filters.
                </p>
            </section>
        </template>
    </div>
</template>
