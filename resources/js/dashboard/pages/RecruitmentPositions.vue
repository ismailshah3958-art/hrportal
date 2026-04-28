<script setup>
import { reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const state = reactive({
    rows: [],
    loading: false,
    saving: false,
    error: '',
    message: '',
    departments: [],
});

const form = reactive({
    title: '',
    department_id: '',
    location: '',
    employment_type: 'full_time',
    openings: 1,
    posted_at: new Date().toISOString().slice(0, 10),
    closing_date: '',
    description: '',
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const [positionsRes, departmentsRes] = await Promise.all([
            window.axios.get('/api/job-positions'),
            window.axios.get('/api/departments'),
        ]);
        state.rows = positionsRes.data.data ?? [];
        state.departments = departmentsRes.data.data ?? [];
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load recruitment positions.';
    } finally {
        state.loading = false;
    }
}

onMounted(load);

async function createPosition() {
    state.saving = true;
    state.error = '';
    state.message = '';
    try {
        await window.axios.post('/api/job-positions', {
            title: form.title,
            department_id: form.department_id || null,
            location: form.location || null,
            employment_type: form.employment_type,
            openings: Number(form.openings || 1),
            posted_at: form.posted_at || null,
            closing_date: form.closing_date || null,
            description: form.description || null,
        });
        state.message = 'Position created.';
        form.title = '';
        form.department_id = '';
        form.location = '';
        form.employment_type = 'full_time';
        form.openings = 1;
        form.posted_at = new Date().toISOString().slice(0, 10);
        form.closing_date = '';
        form.description = '';
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not create position.';
    } finally {
        state.saving = false;
    }
}

async function setStatus(row, status) {
    state.error = '';
    state.message = '';
    try {
        await window.axios.put(`/api/job-positions/${row.id}`, { status });
        state.message = 'Position updated.';
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not update status.';
    }
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div>
            <h2 class="text-lg font-semibold text-white sm:text-xl">Recruitment positions</h2>
        </div>

        <div v-if="state.message" class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ state.message }}</div>
        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <form class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="createPosition">
            <input v-model="form.title" required placeholder="Position title" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <select v-model="form.department_id" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <option value="">Department</option>
                <option v-for="d in state.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
            </select>
            <input v-model="form.location" placeholder="Location (e.g. Lahore)" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <select v-model="form.employment_type" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <option value="full_time">Full time</option>
                <option value="part_time">Part time</option>
                <option value="contract">Contract</option>
                <option value="intern">Intern</option>
            </select>
            <input v-model="form.openings" type="number" min="1" step="1" placeholder="Openings" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.posted_at" type="date" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.closing_date" type="date" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.description" placeholder="Short description" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <div class="xl:col-span-4">
                <button type="submit" :disabled="state.saving" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">Create position</button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5">Title</th>
                        <th class="px-4 py-2.5">Department</th>
                        <th class="px-4 py-2.5">Type</th>
                        <th class="px-4 py-2.5">Openings</th>
                        <th class="px-4 py-2.5">Vacancy</th>
                        <th class="px-4 py-2.5 text-right">Candidates</th>
                        <th class="px-4 py-2.5 text-right">Position</th>
                        <th class="px-4 py-2.5 text-right">Close</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <tr v-if="state.loading"><td colspan="8" class="px-4 py-10 text-center text-slate-500">Loading...</td></tr>
                    <tr v-else-if="!state.rows.length"><td colspan="8" class="px-4 py-10 text-center text-slate-500">No position yet.</td></tr>
                    <tr v-for="row in state.rows" :key="row.id">
                        <td class="px-4 py-3">
                            <p class="font-medium text-white">{{ row.title }}</p>
                            <p class="text-xs text-slate-500">{{ row.location || 'No location' }}</p>
                        </td>
                        <td class="px-4 py-3">{{ row.department_name || '—' }}</td>
                        <td class="px-4 py-3 capitalize">{{ String(row.employment_type || '').replaceAll('_', ' ') }}</td>
                        <td class="px-4 py-3">{{ row.openings }}</td>
                        <td class="px-4 py-3 capitalize">{{ row.status }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                class="inline-flex min-w-[9.5rem] flex-col items-end rounded-xl border border-emerald-500/35 bg-emerald-600/15 px-3 py-2 text-left shadow-sm shadow-emerald-900/20 hover:bg-emerald-600/25"
                                @click="router.push(`/recruitment/${row.id}/applications`)"
                            >
                                <span class="text-xs font-semibold text-emerald-100">Add / manage candidates</span>
                                <span class="mt-0.5 text-[10px] text-slate-400">{{ row.applications_count ?? 0 }} in list</span>
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex flex-col items-end gap-1">
                                <button type="button" class="text-xs text-emerald-400 hover:text-emerald-300" @click="router.push(`/recruitment/${row.id}`)">View</button>
                                <button type="button" class="text-xs text-slate-400 hover:text-white" @click="router.push(`/recruitment/${row.id}/edit`)">Edit</button>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button
                                v-if="row.status !== 'open'"
                                type="button"
                                class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs text-emerald-200 hover:bg-emerald-500/20"
                                @click="setStatus(row, 'open')"
                            >
                                Re-open
                            </button>
                            <button
                                v-else
                                type="button"
                                class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs text-amber-200 hover:bg-amber-500/20"
                                @click="setStatus(row, 'closed')"
                            >
                                Close
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
