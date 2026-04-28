<script setup>
import { reactive, ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const essSelf = computed(() => Boolean(route.meta.essSelf));
const selfEmployeeId = ref(null);

const employeeId = computed(() => {
    if (essSelf.value) {
        return selfEmployeeId.value;
    }

    return route.params.employeeId;
});

function currentYm() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

const monthYm = ref(currentYm());

const state = reactive({
    employee: null,
    attendances: [],
    rangeFrom: '',
    rangeTo: '',
    loading: true,
    error: '',
});

const modal = reactive({
    open: false,
    mode: 'create',
    saving: false,
    errors: {},
    message: '',
    attendanceId: null,
    form: {
        attendance_date: '',
        check_in_at: '',
        check_out_at: '',
        late_minutes: 0,
        early_leave_minutes: 0,
        status: 'present',
        source: 'manual',
        notes: '',
    },
});

const leaveModal = reactive({
    open: false,
    saving: false,
    loadingTypes: false,
    loadingList: false,
    types: [],
    recent: [],
    errors: {},
    message: '',
    form: {
        leave_type_id: '',
        start_date: '',
        end_date: '',
        reason: '',
    },
});

function toLocalInput(iso) {
    if (!iso) {
        return '';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '';
    }
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fromLocalInput(local) {
    if (!local) {
        return null;
    }
    const d = new Date(local);
    if (Number.isNaN(d.getTime())) {
        return null;
    }
    return d.toISOString();
}

const monthLabel = computed(() => {
    const [y, m] = monthYm.value.split('-').map(Number);
    return new Date(y, m - 1, 1).toLocaleString(undefined, { month: 'long', year: 'numeric' });
});

const attendanceByDate = computed(() => {
    const map = {};
    for (const a of state.attendances) {
        map[a.attendance_date] = a;
    }
    return map;
});

const monthDays = computed(() => {
    const [ys, ms] = monthYm.value.split('-');
    const y = Number(ys);
    const m = Number(ms);
    const last = new Date(y, m, 0).getDate();
    const rows = [];
    const map = attendanceByDate.value;
    for (let day = 1; day <= last; day++) {
        const ds = `${ys}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dt = new Date(y, m - 1, day);
        const weekday = dt.toLocaleDateString(undefined, { weekday: 'short' });
        rows.push({
            date: ds,
            day,
            weekday,
            row: map[ds] ?? null,
        });
    }
    return rows;
});

async function loadMonth() {
    if (!employeeId.value) {
        state.loading = false;

        return;
    }
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get(`/api/employees/${employeeId.value}/attendances`, {
            params: { month: monthYm.value },
        });
        state.attendances = data.data ?? [];
        state.rangeFrom = data.range_from ?? '';
        state.rangeTo = data.range_to ?? '';
        state.employee = data.employee ?? state.employee;
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load attendance for this month.';
        state.attendances = [];
    } finally {
        state.loading = false;
    }
}

async function loadAll() {
    await loadMonth();
}

watch(
    () => monthYm.value,
    () => {
        loadMonth();
    }
);

watch(
    () => route.params.employeeId,
    (id) => {
        if (!essSelf.value && id) {
            loadAll();
        }
    }
);

onMounted(async () => {
    if (essSelf.value) {
        try {
            const { data } = await window.axios.get('/api/me');
            if (!data.employee?.id) {
                state.error = 'Your account is not linked to an employee profile.';
                state.loading = false;

                return;
            }
            selfEmployeeId.value = String(data.employee.id);
        } catch (e) {
            state.error = e.response?.data?.message ?? 'Could not verify your profile.';
            state.loading = false;

            return;
        }
    }
    await loadAll();
});

function shiftMonth(delta) {
    const [ys, ms] = monthYm.value.split('-').map(Number);
    const d = new Date(ys, ms - 1 + delta, 1);
    monthYm.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
}

function formatTime(iso) {
    if (!iso) {
        return '—';
    }
    const t = new Date(iso);
    if (Number.isNaN(t.getTime())) {
        return '—';
    }
    return t.toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' });
}

function statusClass(status) {
    const map = {
        present: 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/25',
        remote: 'bg-sky-500/15 text-sky-200 ring-1 ring-sky-500/25',
        half_day: 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-500/25',
        absent: 'bg-red-500/15 text-red-200 ring-1 ring-red-500/25',
        on_leave: 'bg-violet-500/15 text-violet-200 ring-1 ring-violet-500/25',
        holiday: 'bg-slate-500/20 text-slate-300 ring-1 ring-white/10',
    };
    return map[status] ?? map.present;
}

const showHrActions = computed(() => !essSelf.value);
const tableColspan = computed(() => (showHrActions.value ? 10 : 9));

function openCreate(dateStr) {
    modal.mode = 'create';
    modal.attendanceId = null;
    modal.errors = {};
    modal.message = '';
    modal.form = {
        attendance_date: dateStr,
        check_in_at: '',
        check_out_at: '',
        late_minutes: 0,
        early_leave_minutes: 0,
        status: 'present',
        source: 'manual',
        notes: '',
    };
    modal.open = true;
}

function openEdit(row) {
    modal.mode = 'edit';
    modal.attendanceId = row.id;
    modal.errors = {};
    modal.message = '';
    modal.form = {
        attendance_date: row.attendance_date,
        check_in_at: toLocalInput(row.check_in_at),
        check_out_at: toLocalInput(row.check_out_at),
        late_minutes: row.late_minutes ?? 0,
        early_leave_minutes: row.early_leave_minutes ?? 0,
        status: row.status,
        source: row.source ?? 'manual',
        notes: row.notes ?? '',
    };
    modal.open = true;
}

function closeModal() {
    modal.open = false;
}

function fieldError(key) {
    return modal.errors[key]?.[0] ?? '';
}

async function saveModal() {
    modal.saving = true;
    modal.errors = {};
    modal.message = '';
    try {
        if (modal.mode === 'create') {
            await window.axios.post('/api/attendances', {
                employee_id: Number(employeeId.value),
                attendance_date: modal.form.attendance_date,
                check_in_at: fromLocalInput(modal.form.check_in_at),
                check_out_at: fromLocalInput(modal.form.check_out_at),
                late_minutes: Number(modal.form.late_minutes) || 0,
                early_leave_minutes: Number(modal.form.early_leave_minutes) || 0,
                status: modal.form.status,
                source: modal.form.source || 'manual',
                notes: modal.form.notes || null,
            });
        } else {
            await window.axios.put(`/api/attendances/${modal.attendanceId}`, {
                check_in_at: fromLocalInput(modal.form.check_in_at),
                check_out_at: fromLocalInput(modal.form.check_out_at),
                late_minutes: Number(modal.form.late_minutes) || 0,
                early_leave_minutes: Number(modal.form.early_leave_minutes) || 0,
                status: modal.form.status,
                source: modal.form.source || 'manual',
                notes: modal.form.notes || null,
            });
        }
        closeModal();
        await loadMonth();
    } catch (e) {
        if (e.response?.status === 422) {
            modal.errors = e.response.data.errors ?? {};
        } else {
            modal.message = e.response?.data?.message ?? 'Could not save.';
        }
    } finally {
        modal.saving = false;
    }
}

async function removeRow(row) {
    if (!window.confirm(`Delete attendance for ${row.attendance_date}?`)) {
        return;
    }
    try {
        await window.axios.delete(`/api/attendances/${row.id}`);
        await loadMonth();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not delete.';
    }
}

function defaultLeaveDatesFromMonth() {
    const [y, m] = monthYm.value.split('-');
    const day = `${y}-${m}-01`;
    return { start_date: day, end_date: day };
}

async function openLeaveModal() {
    leaveModal.open = true;
    leaveModal.errors = {};
    leaveModal.message = '';
    const d = defaultLeaveDatesFromMonth();
    leaveModal.form = {
        leave_type_id: '',
        start_date: d.start_date,
        end_date: d.end_date,
        reason: '',
    };
    leaveModal.loadingTypes = true;
    try {
        const { data } = await window.axios.get('/api/leave-types');
        leaveModal.types = data.data ?? [];
        if (leaveModal.types.length && !leaveModal.form.leave_type_id) {
            leaveModal.form.leave_type_id = String(leaveModal.types[0].id);
        }
    } catch (e) {
        leaveModal.message = e.response?.data?.message ?? 'Could not load leave types.';
    } finally {
        leaveModal.loadingTypes = false;
    }
    await loadLeaveRequestsBrief();
}

function closeLeaveModal() {
    leaveModal.open = false;
}

async function loadLeaveRequestsBrief() {
    leaveModal.loadingList = true;
    try {
        const { data } = await window.axios.get(`/api/employees/${employeeId.value}/leave-requests`);
        leaveModal.recent = data.data ?? [];
    } catch {
        leaveModal.recent = [];
    } finally {
        leaveModal.loadingList = false;
    }
}

function leaveFieldError(key) {
    return leaveModal.errors[key]?.[0] ?? '';
}

async function saveLeaveRequest() {
    leaveModal.saving = true;
    leaveModal.errors = {};
    leaveModal.message = '';
    try {
        await window.axios.post('/api/leave-requests', {
            employee_id: Number(employeeId.value),
            leave_type_id: Number(leaveModal.form.leave_type_id),
            start_date: leaveModal.form.start_date,
            end_date: leaveModal.form.end_date,
            reason: leaveModal.form.reason || null,
        });
        await loadLeaveRequestsBrief();
        closeLeaveModal();
    } catch (e) {
        if (e.response?.status === 422) {
            leaveModal.errors = e.response.data.errors ?? {};
        } else {
            leaveModal.message = e.response?.data?.message ?? 'Could not submit leave request.';
        }
    } finally {
        leaveModal.saving = false;
    }
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <button
                    v-if="showHrActions"
                    type="button"
                    class="mb-2 text-sm text-slate-400 hover:text-white"
                    @click="router.push('/attendance')"
                >
                    ← Back to employees
                </button>
                <h2 class="text-lg font-semibold text-white sm:text-xl">
                    {{ essSelf ? 'My attendance' : 'Monthly attendance' }}
                </h2>
                <p v-if="state.employee" class="mt-1 text-sm text-slate-400">
                    <span class="font-mono text-emerald-200/90">{{ state.employee.employee_code }}</span>
                    ·
                    <span class="font-medium text-white">{{ state.employee.full_name }}</span>
                </p>
                <p v-if="essSelf" class="mt-1 text-xs text-slate-500">View only — contact HR to correct entries.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="showHrActions"
                    type="button"
                    class="rounded-xl border border-violet-500/30 bg-violet-500/10 px-3 py-2 text-sm font-medium text-violet-100 hover:bg-violet-500/20"
                    @click="openLeaveModal"
                >
                    Request leave
                </button>
                <button
                    type="button"
                    class="rounded-xl border border-white/10 px-3 py-2 text-sm text-slate-300 hover:bg-white/5"
                    @click="shiftMonth(-1)"
                >
                    Previous month
                </button>
                <input
                    v-model="monthYm"
                    type="month"
                    class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                >
                <button
                    type="button"
                    class="rounded-xl border border-white/10 px-3 py-2 text-sm text-slate-300 hover:bg-white/5"
                    @click="shiftMonth(1)"
                >
                    Next month
                </button>
            </div>
        </div>

        <p class="text-sm font-medium text-slate-300">{{ monthLabel }}</p>

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
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Day</th>
                            <th class="px-4 py-3">Check in</th>
                            <th class="px-4 py-3">Check out</th>
                            <th class="px-4 py-3">Work (min)</th>
                            <th class="px-4 py-3">Late (min)</th>
                            <th class="px-4 py-3">Payroll late</th>
                            <th class="px-4 py-3">Early</th>
                            <th class="px-4 py-3">Status</th>
                            <th v-if="showHrActions" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-slate-300">
                        <tr v-if="state.loading">
                            <td :colspan="tableColspan" class="px-4 py-10 text-center text-slate-500">Loading…</td>
                        </tr>
                        <template v-else>
                            <tr
                                v-for="cell in monthDays"
                                :key="cell.date"
                                class="transition hover:bg-white/[0.03]"
                            >
                                <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs text-slate-400">{{ cell.date }}</td>
                                <td class="px-4 py-2.5 text-slate-400">{{ cell.weekday }}</td>
                                <template v-if="cell.row">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-300">{{ formatTime(cell.row.check_in_at) }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-slate-300">{{ formatTime(cell.row.check_out_at) }}</td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ cell.row.work_minutes ?? '—' }}</td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ cell.row.late_minutes }}</td>
                                    <td class="px-4 py-2.5">
                                        <span
                                            v-if="cell.row.late_incident"
                                            class="rounded-md bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-200"
                                        >Yes</span>
                                        <span v-else class="text-slate-600">No</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ cell.row.early_leave_minutes }}</td>
                                    <td class="px-4 py-2.5">
                                        <span
                                            class="inline-flex rounded-lg px-2 py-0.5 text-xs font-medium capitalize"
                                            :class="statusClass(cell.row.status)"
                                        >
                                            {{ String(cell.row.status).replaceAll('_', ' ') }}
                                        </span>
                                    </td>
                                    <td v-if="showHrActions" class="whitespace-nowrap px-4 py-2.5 text-right">
                                        <button
                                            type="button"
                                            class="mr-2 rounded-lg border border-white/10 px-2 py-1 text-xs text-slate-300 hover:bg-white/5"
                                            @click="openEdit(cell.row)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-red-500/30 bg-red-500/10 px-2 py-1 text-xs text-red-200 hover:bg-red-500/20"
                                            @click="removeRow(cell.row)"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </template>
                                <template v-else>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td class="px-4 py-2.5 text-slate-600">—</td>
                                    <td v-if="showHrActions" class="whitespace-nowrap px-4 py-2.5 text-right">
                                        <button
                                            type="button"
                                            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-1 text-xs font-medium text-emerald-200 hover:bg-emerald-500/20"
                                            @click="openCreate(cell.date)"
                                        >
                                            Add
                                        </button>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div
            v-if="showHrActions && modal.open"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            @click.self="closeModal"
        >
            <div
                class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-white/10 bg-[#121820] p-6 shadow-2xl"
                @click.stop
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold text-white">
                        {{ modal.mode === 'create' ? 'Add attendance' : 'Edit attendance' }}
                    </h3>
                    <button
                        type="button"
                        class="rounded-lg border border-white/10 px-2 py-1 text-xs text-slate-400 hover:bg-white/5"
                        @click="closeModal"
                    >
                        Close
                    </button>
                </div>

                <div
                    v-if="modal.message"
                    class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200"
                >
                    {{ modal.message }}
                </div>

                <form class="space-y-4" @submit.prevent="saveModal">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Attendance date *</label>
                        <input
                            v-model="modal.form.attendance_date"
                            type="date"
                            required
                            :disabled="modal.mode === 'edit'"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white disabled:opacity-60 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('attendance_date')" class="mt-1 text-xs text-red-300">{{ fieldError('attendance_date') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Check in</label>
                        <input
                            v-model="modal.form.check_in_at"
                            type="datetime-local"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('check_in_at')" class="mt-1 text-xs text-red-300">{{ fieldError('check_in_at') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Check out</label>
                        <input
                            v-model="modal.form.check_out_at"
                            type="datetime-local"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('check_out_at')" class="mt-1 text-xs text-red-300">{{ fieldError('check_out_at') }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Late (min)</label>
                            <input
                                v-model.number="modal.form.late_minutes"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Early leave (min)</label>
                            <input
                                v-model.number="modal.form.early_leave_minutes"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            >
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Status *</label>
                        <select
                            v-model="modal.form.status"
                            required
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half day</option>
                            <option value="on_leave">On leave</option>
                            <option value="remote">Remote</option>
                            <option value="holiday">Holiday</option>
                        </select>
                        <p v-if="fieldError('status')" class="mt-1 text-xs text-red-300">{{ fieldError('status') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Source</label>
                        <select
                            v-model="modal.form.source"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="manual">Manual</option>
                            <option value="biometric">Biometric</option>
                            <option value="import">Import</option>
                            <option value="api">API</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Notes</label>
                        <textarea
                            v-model="modal.form.notes"
                            rows="2"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                    <div class="flex justify-end gap-2 border-t border-white/10 pt-4">
                        <button
                            type="button"
                            class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/5"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
                            :disabled="modal.saving"
                        >
                            {{ modal.saving ? 'Saving…' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Leave request modal -->
        <div
            v-if="showHrActions && leaveModal.open"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 p-4 sm:items-center"
            role="dialog"
            aria-modal="true"
            @click.self="closeLeaveModal"
        >
            <div
                class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-white/10 bg-[#121820] p-6 shadow-2xl"
                @click.stop
            >
                <div class="mb-4 flex items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold text-white">Request leave</h3>
                    <button
                        type="button"
                        class="rounded-lg border border-white/10 px-2 py-1 text-xs text-slate-400 hover:bg-white/5"
                        @click="closeLeaveModal"
                    >
                        Close
                    </button>
                </div>

                <div
                    v-if="leaveModal.message"
                    class="mb-4 rounded-xl border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-200"
                >
                    {{ leaveModal.message }}
                </div>

                <form class="space-y-4" @submit.prevent="saveLeaveRequest">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Leave type *</label>
                        <select
                            v-model="leaveModal.form.leave_type_id"
                            required
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                            :disabled="leaveModal.loadingTypes"
                        >
                            <option value="" disabled>{{ leaveModal.loadingTypes ? 'Loading…' : 'Select type' }}</option>
                            <option v-for="t in leaveModal.types" :key="t.id" :value="String(t.id)">{{ t.name }}</option>
                        </select>
                        <p v-if="leaveFieldError('leave_type_id')" class="mt-1 text-xs text-red-300">{{ leaveFieldError('leave_type_id') }}</p>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">Start date *</label>
                            <input
                                v-model="leaveModal.form.start_date"
                                type="date"
                                required
                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                            >
                            <p v-if="leaveFieldError('start_date')" class="mt-1 text-xs text-red-300">{{ leaveFieldError('start_date') }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-300">End date *</label>
                            <input
                                v-model="leaveModal.form.end_date"
                                type="date"
                                required
                                class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                            >
                            <p v-if="leaveFieldError('end_date')" class="mt-1 text-xs text-red-300">{{ leaveFieldError('end_date') }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Reason</label>
                        <textarea
                            v-model="leaveModal.form.reason"
                            rows="3"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-violet-500/40 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                            placeholder="Optional note for approver"
                        />
                        <p v-if="leaveFieldError('reason')" class="mt-1 text-xs text-red-300">{{ leaveFieldError('reason') }}</p>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-white/10 pt-4">
                        <button
                            type="button"
                            class="rounded-xl border border-white/10 px-4 py-2 text-sm text-slate-300 hover:bg-white/5"
                            @click="closeLeaveModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50"
                            :disabled="leaveModal.saving || leaveModal.loadingTypes"
                        >
                            {{ leaveModal.saving ? 'Submitting…' : 'Submit request' }}
                        </button>
                    </div>
                </form>

                <div class="mt-6 border-t border-white/10 pt-4">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Recent requests (this employee)</h4>
                    <p v-if="leaveModal.loadingList" class="mt-2 text-sm text-slate-500">Loading…</p>
                    <ul v-else-if="leaveModal.recent.length" class="mt-2 space-y-2 text-sm text-slate-400">
                        <li
                            v-for="r in leaveModal.recent"
                            :key="r.id"
                            class="flex flex-wrap items-baseline justify-between gap-2 rounded-lg border border-white/5 bg-white/[0.02] px-3 py-2"
                        >
                            <span class="text-slate-300">{{ r.leave_type?.name ?? 'Leave' }}</span>
                            <span class="font-mono text-xs text-slate-500">{{ r.start_date }} → {{ r.end_date }}</span>
                            <span class="w-full text-xs capitalize text-slate-500 sm:w-auto">{{ r.status }}</span>
                        </li>
                    </ul>
                    <p v-else class="mt-2 text-sm text-slate-600">No requests yet.</p>
                </div>
            </div>
        </div>
    </div>
</template>
