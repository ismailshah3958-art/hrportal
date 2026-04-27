<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const positionId = computed(() => route.params.positionId);

const state = reactive({
    departments: [],
    loading: false,
    saving: false,
    error: '',
    message: '',
});

const form = reactive({
    title: '',
    department_id: '',
    location: '',
    employment_type: 'full_time',
    openings: 1,
    posted_at: '',
    closing_date: '',
    description: '',
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const [posRes, deptRes] = await Promise.all([
            window.axios.get(`/api/job-positions/${positionId.value}`),
            window.axios.get('/api/departments'),
        ]);
        const row = posRes.data.data;
        state.departments = deptRes.data.data ?? [];
        if (row) {
            form.title = row.title ?? '';
            form.department_id = row.department_id != null ? String(row.department_id) : '';
            form.location = row.location ?? '';
            form.employment_type = row.employment_type || 'full_time';
            form.openings = row.openings ?? 1;
            form.posted_at = row.posted_at ?? '';
            form.closing_date = row.closing_date ?? '';
            form.description = row.description ?? '';
        }
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load position.';
    } finally {
        state.loading = false;
    }
}

onMounted(load);

async function save() {
    state.saving = true;
    state.error = '';
    state.message = '';
    try {
        await window.axios.put(`/api/job-positions/${positionId.value}`, {
            title: form.title,
            department_id: form.department_id || null,
            location: form.location || null,
            employment_type: form.employment_type,
            openings: Number(form.openings || 1),
            posted_at: form.posted_at || null,
            closing_date: form.closing_date || null,
            description: form.description || null,
        });
        state.message = 'Saved.';
        await router.push(`/recruitment/${positionId.value}`);
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not save.';
    } finally {
        state.saving = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <button type="button" class="mb-2 text-xs text-emerald-400 hover:text-emerald-300" @click="router.push(`/recruitment/${positionId}`)">← View position</button>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Edit position</h2>
            </div>
        </div>

        <div v-if="state.message" class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ state.message }}</div>
        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <form v-if="!state.loading" class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-2 xl:grid-cols-4" @submit.prevent="save">
            <input v-model="form.title" required class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <select v-model="form.department_id" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <option value="">Department</option>
                <option v-for="d in state.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
            </select>
            <input v-model="form.location" placeholder="Location" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <select v-model="form.employment_type" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <option value="full_time">Full time</option>
                <option value="part_time">Part time</option>
                <option value="contract">Contract</option>
                <option value="intern">Intern</option>
            </select>
            <input v-model="form.openings" type="number" min="1" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.posted_at" type="date" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.closing_date" type="date" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <textarea v-model="form.description" rows="3" placeholder="Description" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white sm:col-span-2 xl:col-span-4"></textarea>
            <div class="xl:col-span-4 flex gap-2">
                <button type="submit" :disabled="state.saving" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">Save</button>
                <button type="button" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/5" @click="router.push('/recruitment')">Cancel</button>
            </div>
        </form>
        <div v-else class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-slate-500">Loading...</div>
    </div>
</template>
