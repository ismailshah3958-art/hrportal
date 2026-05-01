<script setup>
import { reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const state = reactive({
    rows: [],
    meta: null,
    links: null,
    loading: false,
    error: '',
    search: '',
    page: 1,
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get('/api/employees', {
            params: {
                page: state.page,
                search: state.search.trim() || undefined,
            },
        });
        state.rows = data.data;
        state.meta = data.meta;
        state.links = data.links;
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load employees.';
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

const pkMoney = new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR',
    maximumFractionDigits: 0,
});

function formatSalary(val) {
    if (val == null || val === '') {
        return '—';
    }
    const n = Number(val);
    if (Number.isNaN(n)) {
        return String(val);
    }
    return pkMoney.format(n);
}

function statusClass(status) {
    const map = {
        active: 'bg-amber-500/25 text-amber-50 ring-1 ring-amber-400/50',
        on_leave: 'bg-orange-500/20 text-orange-100 ring-1 ring-orange-400/40',
        resigned: 'bg-slate-500/20 text-slate-300 ring-1 ring-white/10',
        terminated: 'bg-red-500/15 text-red-200 ring-1 ring-red-500/25',
    };
    return map[status] ?? map.active;
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Employees</h2>
                <p class="mt-1 text-sm text-slate-500">Search the roster and add new employees from here.</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-amber-900/25 transition hover:bg-amber-500"
                @click="router.push('/employees/create')"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add employee
            </button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
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
                    placeholder="Name, code, email, CNIC…"
                    class="w-full rounded-xl border border-white/10 bg-white/5 py-2.5 pl-10 pr-4 text-sm text-white placeholder:text-slate-500 focus:border-amber-500/40 focus:outline-none focus:ring-2 focus:ring-amber-500/20"
                    @input="onSearchInput"
                >
            </div>
            <p v-if="state.meta" class="text-xs text-slate-500">
                Total <span class="font-medium text-slate-400">{{ state.meta.total }}</span> records
            </p>
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
                            <th class="px-4 py-3">Designation</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Salary</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <tr v-if="state.loading">
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">Loading…</td>
                        </tr>
                        <tr v-else-if="!state.rows.length">
                            <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                                No employees found. Click “Add employee” to create the first record.
                            </td>
                        </tr>
                        <tr
                            v-for="row in state.rows"
                            :key="row.id"
                            class="transition hover:bg-white/[0.04]"
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
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-amber-200/95">
                                {{ row.employee_code }}
                            </td>
                            <td class="px-4 py-3 font-medium text-white">{{ row.full_name }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ row.department?.name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ row.designation?.name ?? '—' }}</td>
                            <td class="max-w-[200px] truncate px-4 py-3 text-slate-400">{{ row.work_email ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-400">{{ formatSalary(row.salary) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-lg px-2 py-0.5 text-xs font-medium capitalize"
                                    :class="statusClass(row.status)"
                                >
                                    {{ String(row.status).replace('_', ' ') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="mr-2 rounded-lg border border-white/10 px-2.5 py-1 text-xs font-medium text-slate-300 hover:bg-white/5"
                                    @click="router.push(`/employees/${row.id}`)"
                                >
                                    View
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg border border-amber-500/45 bg-amber-500/20 px-2.5 py-1 text-xs font-medium text-amber-50 hover:bg-amber-500/30"
                                    @click="router.push(`/employees/${row.id}/edit`)"
                                >
                                    Edit
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
