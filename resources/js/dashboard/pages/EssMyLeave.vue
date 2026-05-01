<script setup>
import { reactive, ref, computed, onMounted, watch } from 'vue';

const monthYm = ref(currentYm());

function currentYm() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

function defaultDatesFromMonth() {
    const m = monthYm.value;
    const day = `${m}-01`;
    return { start_date: day, end_date: day };
}

const state = reactive({
    session: null,
    types: [],
    list: [],
    loading: true,
    error: '',
});

const form = reactive({
    leave_type_id: '',
    start_date: '',
    end_date: '',
    reason: '',
});

const opts = reactive({
    saving: false,
    errors: {},
    message: '',
});

const monthLabel = computed(() => {
    const [y, m] = monthYm.value.split('-').map(Number);
    return new Date(y, m - 1, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' });
});
const leaveTypeCards = computed(() => {
    const requested = ['annual', 'casual', 'sick', 'medical'];
    const allTypes = state.types ?? [];
    const rows = state.list ?? [];

    return requested.map((key) => {
        const matched = allTypes.find((t) => {
            const name = String(t.name || '').toLowerCase();
            const code = String(t.code || '').toLowerCase();
            return name.includes(key) || code.includes(key);
        });

        const typeId = Number(matched?.id ?? 0);
        const entitlement = Number(matched?.default_days_per_year ?? 0);
        const used = rows
            .filter((r) => Number(r.leave_type_id) === typeId && String(r.status || '').toLowerCase() === 'approved')
            .reduce((sum, r) => sum + Number(r.total_days || 0), 0);

        return {
            id: matched?.id ?? `virtual-${key}`,
            name: matched?.name ?? `${key.charAt(0).toUpperCase()}${key.slice(1)} leave`,
            balance: Math.max(0, entitlement - used),
        };
    });
});

async function loadSession() {
    const { data } = await window.axios.get('/api/me');
    state.session = data;
}

async function loadTypes() {
    const { data } = await window.axios.get('/api/leave-types');
    state.types = data.data ?? [];
    if (state.types.length && !form.leave_type_id) {
        form.leave_type_id = String(state.types[0].id);
    }
}

async function loadList() {
    const emp = state.session?.employee;
    if (!emp?.id) {
        state.list = [];

        return;
    }
    const { data } = await window.axios.get(`/api/employees/${emp.id}/leave-requests`);
    state.list = data.data ?? [];
}

async function loadAll() {
    state.loading = true;
    state.error = '';
    try {
        await loadSession();
        if (!state.session?.employee) {
            state.error = 'Your login is not linked to an employee profile. Ask HR to link your user account.';

            return;
        }
        const d = defaultDatesFromMonth();
        form.start_date = d.start_date;
        form.end_date = d.end_date;
        form.reason = '';
        await loadTypes();
        await loadList();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load.';
    } finally {
        state.loading = false;
    }
}

onMounted(loadAll);

watch(monthYm, () => {
    const d = defaultDatesFromMonth();
    form.start_date = d.start_date;
    form.end_date = d.end_date;
});

function fieldError(key) {
    return opts.errors[key]?.[0] ?? '';
}

async function submit() {
    opts.saving = true;
    opts.errors = {};
    opts.message = '';
    try {
        await window.axios.post('/api/leave-requests', {
            leave_type_id: Number(form.leave_type_id),
            start_date: form.start_date,
            end_date: form.end_date,
            reason: form.reason || null,
        });
        opts.message = 'Leave request submitted. Your reporting manager and HR have been notified.';
        const d = defaultDatesFromMonth();
        form.start_date = d.start_date;
        form.end_date = d.end_date;
        form.reason = '';
        await loadList();
    } catch (e) {
        if (e.response?.status === 422) {
            opts.errors = e.response.data.errors ?? {};
        } else {
            opts.message = e.response?.data?.message ?? 'Could not submit.';
        }
    } finally {
        opts.saving = false;
    }
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div>
            <h2 class="text-lg font-semibold text-white sm:text-xl">My leave</h2>
            <p class="mt-1 text-sm text-slate-500">Apply for leave; your manager and HR are notified for approval.</p>
        </div>

        <div v-if="state.error" class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
            {{ state.error }}
        </div>

        <div v-else-if="state.loading" class="text-sm text-slate-500">Loading…</div>

        <template v-else>
            <div v-if="opts.message" class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                {{ opts.message }}
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h3 class="text-sm font-semibold text-white">Leave types</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="t in leaveTypeCards"
                        :key="t.id"
                        class="rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3"
                    >
                        <p class="text-sm font-medium text-slate-200">{{ t.name }}</p>
                        <p class="mt-1 text-xs text-slate-500">
                            Balance: <span class="font-semibold text-white tabular-nums">{{ t.balance }}</span>
                        </p>
                    </article>
                </div>
            </div>

            <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                <h3 class="text-sm font-semibold text-white">New request</h3>
                <p class="mt-1 text-xs text-slate-500">Default dates use the first day of the selected month.</p>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-400">Month hint</label>
                        <input v-model="monthYm" type="month" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                    </div>
                    <p class="text-xs text-slate-500">{{ monthLabel }}</p>
                </div>
                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Leave type *</label>
                        <select v-model="form.leave_type_id" required class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100">
                            <option v-for="t in state.types" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                        </select>
                        <p v-if="fieldError('leave_type_id')" class="mt-1 text-xs text-red-300">{{ fieldError('leave_type_id') }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Start *</label>
                            <input v-model="form.start_date" type="date" required class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white">
                            <p v-if="fieldError('start_date')" class="mt-1 text-xs text-red-300">{{ fieldError('start_date') }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">End *</label>
                            <input v-model="form.end_date" type="date" required class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white">
                            <p v-if="fieldError('end_date')" class="mt-1 text-xs text-red-300">{{ fieldError('end_date') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Reason</label>
                        <textarea v-model="form.reason" rows="3" class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white" placeholder="Optional" />
                    </div>
                    <button type="submit" class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50" :disabled="opts.saving">
                        {{ opts.saving ? 'Submitting…' : 'Submit request' }}
                    </button>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-4 py-3 text-sm font-semibold text-white">My requests</h3>
                <ul v-if="state.list.length" class="divide-y divide-white/5 text-sm text-slate-300">
                    <li v-for="r in state.list" :key="r.id" class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                        <span>{{ r.leave_type?.name ?? 'Leave' }}</span>
                        <span class="font-mono text-xs text-slate-500">{{ r.start_date }} → {{ r.end_date }}</span>
                        <span class="rounded-md border border-white/10 px-2 py-0.5 text-xs capitalize">{{ r.status }}</span>
                    </li>
                </ul>
                <p v-else class="px-4 py-6 text-sm text-slate-500">No requests yet.</p>
            </div>
        </template>
    </div>
</template>

