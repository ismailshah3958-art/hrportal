<script setup>
import { computed, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const state = reactive({
    rows: [],
    loading: false,
    error: '',
    canManage: false,
    q: '',
    onlyPinned: false,
});

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const [feedRes, meRes] = await Promise.all([
            window.axios.get('/api/announcements/feed'),
            window.axios.get('/api/me'),
        ]);
        state.rows = feedRes.data.data ?? [];
        state.canManage = !!(meRes.data?.flags?.hr_announcements_manage);
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load announcements.';
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
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-white sm:text-xl">Announcements</h2>
            <button
                v-if="state.canManage"
                type="button"
                class="rounded-xl border border-white/15 px-4 py-2 text-sm text-slate-200 hover:bg-white/5"
                @click="router.push('/announcements')"
            >
                Post / manage
            </button>
        </div>

        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <div class="grid gap-3 sm:grid-cols-4">
            <input
                v-model.trim="state.q"
                type="text"
                placeholder="Search announcements..."
                class="sm:col-span-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white placeholder:text-slate-500"
            >
            <label class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/[0.03] px-3 py-2 text-sm text-slate-300">
                <input v-model="state.onlyPinned" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/10 text-emerald-500">
                Pinned only
            </label>
        </div>

        <div v-if="state.loading" class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-slate-500">Loading...</div>

        <ul v-else-if="filteredRows.length" class="space-y-3">
            <li
                v-for="a in filteredRows"
                :key="a.id"
                class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-4"
            >
                <p class="text-sm font-semibold text-white">
                    <span v-if="a.is_pinned" class="mr-1 text-amber-400">*</span>{{ a.title }}
                </p>
                <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-300">{{ a.body }}</p>
            </li>
        </ul>

        <p v-else class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-12 text-center text-sm text-slate-500">No announcements match current filter.</p>
    </div>
</template>
