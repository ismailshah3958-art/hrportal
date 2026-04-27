<script setup>
import { reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const state = reactive({
    rows: [],
    meta: null,
    loading: false,
    error: '',
    search: '',
    page: 1,
});

const zkSync = reactive({
    loading: false,
    message: '',
    error: '',
});

async function syncZkteco() {
    zkSync.loading = true;
    zkSync.message = '';
    zkSync.error = '';
    try {
        const { data } = await window.axios.post('/api/zkteco/sync-attendance');
        if (data.message) {
            zkSync.error = data.message;
        } else {
            const ids = data.unmapped_device_user_ids?.length
                ? ` Unmapped device IDs: ${data.unmapped_device_user_ids.join(', ')}.`
                : '';
            zkSync.message = `Upserted ${data.synced ?? 0} day row(s); read ${data.logs_read ?? 0} log line(s) from device.${ids}`;
        }
    } catch (e) {
        zkSync.error = e.response?.data?.message ?? 'ZKTeco sync failed.';
    } finally {
        zkSync.loading = false;
    }
}

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get('/api/employees', {
            params: {
                page: state.page,
                search: state.search.trim() || undefined,
                per_page: 15,
            },
        });
        state.rows = data.data;
        state.meta = data.meta;
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load employees.';
        state.rows = [];
    } finally {
        state.loading = false;
    }
}

function goPage(p) {
    if (p < 1 || (state.meta && p > state.meta.last_page)) {
        return;
    }
    state.page = p;
    load();
}

let searchTimer;
function onSearchInput() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        state.page = 1;
        load();
    }, 350);
}

onMounted(load);

function initials(name) {
    if (!name) {
        return '?';
    }
    const parts = String(name).trim().split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return String(name).slice(0, 2).toUpperCase();
}
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-lg font-semibold text-white sm:text-xl">Attendance</h2>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative max-w-md flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                        />
                    </svg>
                </span>
                <input
                    v-model="state.search"
                    type="search"
                    placeholder="Name, code, email…"
                    class="w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                    @input="onSearchInput"
                >
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <p v-if="state.meta" class="text-xs text-slate-500">
                    Total <span class="font-medium text-slate-400">{{ state.meta.total }}</span> employees
                </p>
                <button
                    type="button"
                    class="rounded-xl border border-cyan-500/30 bg-cyan-500/10 px-3 py-2 text-xs font-medium text-cyan-100 hover:bg-cyan-500/20 disabled:opacity-40"
                    :disabled="zkSync.loading"
                    title="Requires ZKTECO_ENABLED and PHP sockets; server must reach device on UDP 4370"
                    @click="syncZkteco"
                >
                    {{ zkSync.loading ? 'Syncing…' : 'Sync ZKTeco' }}
                </button>
            </div>
        </div>

        <div
            v-if="zkSync.message"
            class="rounded-xl border border-cyan-500/25 bg-cyan-500/10 px-4 py-3 text-sm text-cyan-100"
        >
            {{ zkSync.message }}
        </div>
        <div
            v-if="zkSync.error"
            class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100"
        >
            {{ zkSync.error }}
        </div>

        <div
            v-if="state.error"
            class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"
        >
            {{ state.error }}
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                    <thead class="bg-white/5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="w-14 px-4 py-3" />
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Department</th>
                            <th class="px-4 py-3 text-right">Attendance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <tr v-if="state.loading">
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">Loading…</td>
                        </tr>
                        <tr v-else-if="!state.rows.length">
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No employees found.</td>
                        </tr>
                        <tr
                            v-for="row in state.rows"
                            :key="row.id"
                            class="cursor-pointer transition hover:bg-white/[0.04]"
                            @click="router.push(`/attendance/${row.id}`)"
                        >
                            <td class="px-4 py-2">
                                <div
                                    class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/5 text-xs font-semibold text-slate-300"
                                >
                                    <img
                                        v-if="row.profile_photo_url"
                                        :src="row.profile_photo_url"
                                        alt=""
                                        class="h-full w-full object-cover"
                                    >
                                    <span v-else>{{ initials(row.full_name) }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-emerald-200/90">
                                {{ row.employee_code }}
                            </td>
                            <td class="px-4 py-3 font-medium text-white">{{ row.full_name }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ row.department?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-medium text-emerald-200 hover:bg-emerald-500/20"
                                    @click.stop="router.push(`/attendance/${row.id}`)"
                                >
                                    Monthly record
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="state.meta && state.meta.last_page > 1"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-white/10 px-4 py-3"
            >
                <p class="text-xs text-slate-500">
                    Page {{ state.meta.current_page }} / {{ state.meta.last_page }}
                </p>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-white/5 disabled:opacity-40"
                        :disabled="state.meta.current_page <= 1"
                        @click="goPage(state.meta.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-white/5 disabled:opacity-40"
                        :disabled="state.meta.current_page >= state.meta.last_page"
                        @click="goPage(state.meta.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
