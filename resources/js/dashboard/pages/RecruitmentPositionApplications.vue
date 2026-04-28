<script setup>
import { reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const stages = ['applied', 'interview', 'offer', 'hired', 'rejected'];
/** Matches DB: interviews.status default + comments in migration */
const interviewStatuses = ['scheduled', 'completed', 'cancelled', 'no_show'];

const state = reactive({
    position: null,
    applications: [],
    loading: false,
    saving: false,
    error: '',
    message: '',
    filterStage: 'all',
    filterInterview: 'all',
    search: '',
});

const form = reactive({
    full_name: '',
    email: '',
    phone: '',
    notes: '',
});

const scheduleDraft = reactive({});

const positionId = computed(() => route.params.positionId);

function getScheduleDraft(appId) {
    if (!scheduleDraft[appId]) {
        scheduleDraft[appId] = { when: '', duration: 60, mode: 'video' };
    }
    return scheduleDraft[appId];
}

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get(`/api/job-positions/${positionId.value}/applications`);
        state.position = data.position ?? null;
        state.applications = data.data ?? [];
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load applications.';
        state.position = null;
        state.applications = [];
    } finally {
        state.loading = false;
    }
}

onMounted(load);

async function addApplication() {
    state.saving = true;
    state.error = '';
    state.message = '';
    try {
        await window.axios.post(`/api/job-positions/${positionId.value}/applications`, {
            full_name: form.full_name,
            email: form.email,
            phone: form.phone || null,
            notes: form.notes || null,
        });
        state.message = 'Candidate added.';
        form.full_name = '';
        form.email = '';
        form.phone = '';
        form.notes = '';
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not add application.';
    } finally {
        state.saving = false;
    }
}

async function updateStage(row, stage) {
    state.error = '';
    state.message = '';
    try {
        await window.axios.put(`/api/job-positions/${positionId.value}/applications/${row.id}`, { stage });
        state.message = 'Pipeline updated.';
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not update stage.';
    }
}

async function scheduleInterview(row) {
    const d = getScheduleDraft(row.id);
    if (!d.when) {
        state.error = 'Pick date & time for the interview.';
        return;
    }
    state.error = '';
    state.message = '';
    try {
        await window.axios.post(`/api/job-positions/${positionId.value}/applications/${row.id}/interviews`, {
            scheduled_at: d.when,
            duration_minutes: Number(d.duration) || 60,
            mode: d.mode || 'video',
        });
        state.message = 'Interview scheduled.';
        d.when = '';
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not schedule interview.';
    }
}

function formatInterviewWhen(iso) {
    if (!iso) return '';
    try {
        return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    } catch {
        return iso;
    }
}

function interviewStatusLabel(s) {
    if (s === 'no_show') {
        return 'No show';
    }
    return s ? String(s).replaceAll('_', ' ') : '—';
}

function appInterviews(row) {
    return row.interviews ?? [];
}

function countAppsNoInterviews() {
    return state.applications.filter((row) => appInterviews(row).length === 0).length;
}

function countAppsWithInterviewStatus(status) {
    return state.applications.filter((row) => appInterviews(row).some((iv) => iv.status === status)).length;
}

function matchesInterviewFilter(row) {
    const f = state.filterInterview;
    if (f === 'all') {
        return true;
    }
    const ivs = appInterviews(row);
    if (f === 'none') {
        return ivs.length === 0;
    }
    return ivs.some((iv) => iv.status === f);
}

function escapeCsv(val) {
    const s = val == null ? '' : String(val);
    if (/[",\n\r]/.test(s)) {
        return `"${s.replaceAll('"', '""')}"`;
    }
    return s;
}

function exportFilteredCsv() {
    const posTitle = (state.position?.title || 'position').replaceAll(/[^\w\-]+/g, '_').slice(0, 60);
    const headers = ['Candidate', 'Email', 'Phone', 'Stage', 'Notes', 'Interviews'];
    const lines = [`\uFEFF${headers.join(',')}`];
    for (const r of filteredApplications.value) {
        const ivSummary = appInterviews(r)
            .map((iv) => `${formatInterviewWhen(iv.scheduled_at)} (${iv.status})`)
            .join(' | ');
        const row = [
            r.full_name,
            r.email,
            r.phone ?? '',
            r.stage ?? '',
            (r.notes ?? '').replaceAll(/\s+/g, ' ').trim(),
            ivSummary,
        ].map(escapeCsv);
        lines.push(row.join(','));
    }
    const blob = new Blob([lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${posTitle}-applications.csv`;
    a.rel = 'noopener';
    a.click();
    URL.revokeObjectURL(url);
}

const stageCounts = computed(() => {
    const counts = Object.fromEntries(stages.map((s) => [s, 0]));
    for (const row of state.applications) {
        const s = row.stage;
        if (s && counts[s] !== undefined) {
            counts[s] += 1;
        }
    }
    return counts;
});

const filteredApplications = computed(() => {
    const q = state.search.trim().toLowerCase();
    return state.applications.filter((row) => {
        if (state.filterStage !== 'all' && row.stage !== state.filterStage) {
            return false;
        }
        if (!matchesInterviewFilter(row)) {
            return false;
        }
        if (!q) return true;
        const blob = [row.full_name, row.email, row.phone, row.notes].filter(Boolean).join(' ').toLowerCase();
        return blob.includes(q);
    });
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <button type="button" class="mb-2 text-xs text-emerald-400 hover:text-emerald-300" @click="router.push('/recruitment')">← Back to positions</button>
                <h2 class="text-lg font-semibold text-white sm:text-xl">
                    {{ state.position?.title ?? 'Applications' }}
                </h2>
                <p v-if="state.position" class="mt-1 text-sm text-slate-500">
                    {{ state.position.department_name || 'No department' }} · {{ state.position.location || 'No location' }} · {{ state.position.status }}
                </p>
            </div>
            <div v-if="state.position" class="flex flex-wrap gap-2">
                <button type="button" class="rounded-xl border border-white/10 px-3 py-2 text-xs text-slate-300 hover:bg-white/5" @click="router.push(`/recruitment/${state.position.id}`)">View position</button>
                <button type="button" class="rounded-xl border border-white/10 px-3 py-2 text-xs text-slate-300 hover:bg-white/5" @click="router.push(`/recruitment/${state.position.id}/edit`)">Edit position</button>
            </div>
        </div>

        <div v-if="state.message" class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ state.message }}</div>
        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <form class="grid gap-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="addApplication">
            <input v-model="form.full_name" required placeholder="Candidate name" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.email" type="email" required placeholder="Email" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.phone" placeholder="Phone" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <input v-model="form.notes" placeholder="Notes" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <div class="sm:col-span-2 lg:col-span-4">
                <button type="submit" :disabled="state.saving" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">Add candidate</button>
            </div>
        </form>

        <section v-if="!state.loading && state.applications.length" class="space-y-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pipeline snapshot</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <button
                    v-for="s in stages"
                    :key="s"
                    type="button"
                    class="rounded-xl border px-3 py-3 text-left transition"
                    :class="state.filterStage === s ? 'border-emerald-500/50 bg-emerald-500/10' : 'border-white/10 bg-white/[0.03] hover:border-white/20'"
                    @click="state.filterStage = state.filterStage === s ? 'all' : s"
                >
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">{{ s }}</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-white">{{ stageCounts[s] ?? 0 }}</p>
                    <p class="mt-1 text-[10px] text-slate-500">Click to filter</p>
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <input
                    v-model.trim="state.search"
                    type="search"
                    placeholder="Search name, email, phone, notes…"
                    class="min-w-[12rem] flex-1 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500"
                >
                <select v-model="state.filterStage" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    <option value="all">All stages</option>
                    <option v-for="s in stages" :key="'opt-' + s" :value="s">{{ s }}</option>
                </select>
                <span class="text-xs text-slate-500">{{ filteredApplications.length }} shown</span>
                <button
                    type="button"
                    class="rounded-xl border border-white/10 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-white/5"
                    :disabled="!filteredApplications.length"
                    @click="exportFilteredCsv"
                >
                    Export CSV
                </button>
            </div>

            <div>
                <h4 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">Interview status (candidates)</h4>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-lg border px-2.5 py-1.5 text-xs transition"
                        :class="state.filterInterview === 'all' ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-100' : 'border-white/10 text-slate-300 hover:bg-white/5'"
                        @click="state.filterInterview = 'all'"
                    >
                        Any · {{ state.applications.length }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border px-2.5 py-1.5 text-xs transition"
                        :class="state.filterInterview === 'none' ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-100' : 'border-white/10 text-slate-300 hover:bg-white/5'"
                        @click="state.filterInterview = state.filterInterview === 'none' ? 'all' : 'none'"
                    >
                        No interviews · {{ countAppsNoInterviews() }}
                    </button>
                    <button
                        v-for="st in interviewStatuses"
                        :key="'iv-' + st"
                        type="button"
                        class="rounded-lg border px-2.5 py-1.5 text-xs capitalize transition"
                        :class="state.filterInterview === st ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-100' : 'border-white/10 text-slate-300 hover:bg-white/5'"
                        @click="state.filterInterview = state.filterInterview === st ? 'all' : st"
                    >
                        {{ interviewStatusLabel(st) }} · {{ countAppsWithInterviewStatus(st) }}
                    </button>
                </div>
                <p class="mt-2 text-[10px] text-slate-600">Shows candidates with at least one interview in that status. Click again to clear.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[10px] uppercase tracking-wider text-slate-500">Or select</span>
                <select v-model="state.filterInterview" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    <option value="all">Any interview</option>
                    <option value="none">No interviews scheduled</option>
                    <option v-for="st in interviewStatuses" :key="'sel-' + st" :value="st">
                        Has {{ interviewStatusLabel(st) }} · {{ countAppsWithInterviewStatus(st) }}
                    </option>
                </select>
            </div>
        </section>

        <div class="overflow-x-auto overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5">Candidate</th>
                        <th class="px-4 py-2.5">Contact</th>
                        <th class="px-4 py-2.5">Pipeline</th>
                        <th class="px-4 py-2.5">Interview</th>
                        <th class="px-4 py-2.5">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <tr v-if="state.loading"><td colspan="5" class="px-4 py-10 text-center text-slate-500">Loading...</td></tr>
                    <tr v-else-if="!state.applications.length"><td colspan="5" class="px-4 py-10 text-center text-slate-500">No applications yet.</td></tr>
                    <tr v-else-if="!filteredApplications.length"><td colspan="5" class="px-4 py-10 text-center text-slate-500">No applications match filters.</td></tr>
                    <tr v-for="row in filteredApplications" :key="row.id">
                        <td class="px-4 py-3 font-medium text-white">{{ row.full_name }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div>{{ row.email }}</div>
                            <div class="text-slate-500">{{ row.phone || '—' }}</div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <select
                                :value="row.stage"
                                class="mb-2 block w-full max-w-[11rem] rounded-lg border border-white/10 bg-white/5 px-2 py-1.5 text-xs capitalize text-white"
                                @change="updateStage(row, $event.target.value)"
                            >
                                <option v-for="s in stages" :key="s" :value="s">{{ s }}</option>
                            </select>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="rounded border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-200 hover:bg-emerald-500/20" @click="updateStage(row, 'hired')">Hire</button>
                                <button type="button" class="rounded border border-red-500/30 bg-red-500/10 px-2 py-0.5 text-[10px] text-red-200 hover:bg-red-500/20" @click="updateStage(row, 'rejected')">Reject</button>
                                <button type="button" class="rounded border border-violet-500/30 bg-violet-500/10 px-2 py-0.5 text-[10px] text-violet-200 hover:bg-violet-500/20" @click="updateStage(row, 'interview')">Interview</button>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top text-xs">
                            <ul v-if="(row.interviews || []).length" class="mb-2 space-y-1 text-slate-400">
                                <li v-for="iv in row.interviews" :key="iv.id" class="rounded border border-white/5 bg-black/20 px-2 py-1">
                                    {{ formatInterviewWhen(iv.scheduled_at) }}
                                    <span class="text-slate-500"> · {{ iv.mode }} · {{ iv.status }}</span>
                                </li>
                            </ul>
                            <div class="flex flex-col gap-1">
                                <input
                                    v-model="getScheduleDraft(row.id).when"
                                    type="datetime-local"
                                    class="max-w-[12rem] rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs text-white"
                                >
                                <div class="flex flex-wrap items-center gap-1">
                                    <select v-model="getScheduleDraft(row.id).duration" class="rounded border border-white/10 bg-white/5 px-1 py-1 text-[10px] text-white">
                                        <option :value="30">30 min</option>
                                        <option :value="45">45 min</option>
                                        <option :value="60">60 min</option>
                                        <option :value="90">90 min</option>
                                    </select>
                                    <select v-model="getScheduleDraft(row.id).mode" class="rounded border border-white/10 bg-white/5 px-1 py-1 text-[10px] text-white">
                                        <option value="video">Video</option>
                                        <option value="in_person">In person</option>
                                        <option value="phone">Phone</option>
                                    </select>
                                    <button type="button" class="rounded bg-slate-600 px-2 py-1 text-[10px] text-white hover:bg-slate-500" @click="scheduleInterview(row)">Schedule</button>
                                </div>
                            </div>
                        </td>
                        <td class="max-w-[10rem] px-4 py-3 align-top text-xs text-slate-400">{{ row.notes || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
