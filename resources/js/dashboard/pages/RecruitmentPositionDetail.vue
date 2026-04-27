<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const positionId = computed(() => route.params.positionId);

const state = reactive({
    row: null,
    loading: false,
    error: '',
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get(`/api/job-positions/${positionId.value}`);
        state.row = data.data ?? null;
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load position.';
        state.row = null;
    } finally {
        state.loading = false;
    }
}

onMounted(load);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <button type="button" class="mb-2 text-xs text-emerald-400 hover:text-emerald-300" @click="router.push('/recruitment')">← Back to positions</button>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Position details</h2>
            </div>
            <div v-if="state.row" class="flex flex-wrap gap-2">
                <button type="button" class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/5" @click="router.push(`/recruitment/${state.row.id}/edit`)">Edit</button>
                <button type="button" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500" @click="router.push(`/recruitment/${state.row.id}/applications`)">Pipeline &amp; interviews</button>
            </div>
        </div>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>
        <div v-if="state.loading" class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-slate-500">Loading...</div>

        <div v-else-if="state.row" class="space-y-4 rounded-2xl border border-white/10 bg-white/[0.03] p-6">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Title</dt>
                    <dd class="mt-1 text-white">{{ state.row.title }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</dt>
                    <dd class="mt-1 capitalize text-slate-300">{{ state.row.status }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Department</dt>
                    <dd class="mt-1 text-slate-300">{{ state.row.department_name || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Location</dt>
                    <dd class="mt-1 text-slate-300">{{ state.row.location || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Employment type</dt>
                    <dd class="mt-1 capitalize text-slate-300">{{ String(state.row.employment_type || '').replaceAll('_', ' ') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Openings</dt>
                    <dd class="mt-1 text-slate-300">{{ state.row.openings }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Posted</dt>
                    <dd class="mt-1 text-slate-300">{{ state.row.posted_at || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Closing</dt>
                    <dd class="mt-1 text-slate-300">{{ state.row.closing_date || '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Description</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-sm text-slate-300">{{ state.row.description || '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Applications</dt>
                    <dd class="mt-1 text-slate-300">{{ state.row.applications_count ?? 0 }}</dd>
                </div>
            </dl>
        </div>
    </div>
</template>
