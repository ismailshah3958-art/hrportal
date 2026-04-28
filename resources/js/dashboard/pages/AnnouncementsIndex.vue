<script setup>
import { reactive, onMounted } from 'vue';

const state = reactive({
    rows: [],
    departments: [],
    loading: false,
    saving: false,
    error: '',
    message: '',
});

const form = reactive({
    title: '',
    body: '',
    published_at: '',
    expires_at: '',
    is_pinned: false,
    department_ids: [],
});

const edit = reactive({
    id: null,
    title: '',
    body: '',
    published_at: '',
    expires_at: '',
    is_pinned: false,
    department_ids: [],
});

function toggleDept(list, id) {
    const n = Number(id);
    const i = list.indexOf(n);
    if (i === -1) {
        list.push(n);
    } else {
        list.splice(i, 1);
    }
}

function isDeptChecked(list, id) {
    return list.includes(Number(id));
}

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const [ann, dept] = await Promise.all([
            window.axios.get('/api/announcements'),
            window.axios.get('/api/departments'),
        ]);
        state.rows = ann.data.data ?? [];
        state.departments = dept.data.data ?? [];
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load announcements.';
    } finally {
        state.loading = false;
    }
}

onMounted(load);

async function createAnnouncement() {
    state.saving = true;
    state.error = '';
    state.message = '';
    try {
        await window.axios.post('/api/announcements', {
            title: form.title,
            body: form.body,
            published_at: form.published_at || null,
            expires_at: form.expires_at || null,
            is_pinned: form.is_pinned,
            department_ids: form.department_ids.length ? form.department_ids : [],
        });
        state.message = 'Announcement created.';
        form.title = '';
        form.body = '';
        form.published_at = '';
        form.expires_at = '';
        form.is_pinned = false;
        form.department_ids = [];
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not save.';
    } finally {
        state.saving = false;
    }
}

function startEdit(row) {
    edit.id = row.id;
    edit.title = row.title;
    edit.body = row.body;
    edit.published_at = row.published_at ? row.published_at.slice(0, 16) : '';
    edit.expires_at = row.expires_at ? row.expires_at.slice(0, 16) : '';
    edit.is_pinned = !!row.is_pinned;
    const ids = row.target_audience?.department_ids ?? [];
    edit.department_ids = Array.isArray(ids) ? [...ids] : [];
}

function cancelEdit() {
    edit.id = null;
}

async function saveEdit() {
    if (!edit.id) return;
    state.saving = true;
    state.error = '';
    state.message = '';
    try {
        await window.axios.put(`/api/announcements/${edit.id}`, {
            title: edit.title,
            body: edit.body,
            published_at: edit.published_at || null,
            expires_at: edit.expires_at || null,
            is_pinned: edit.is_pinned,
            department_ids: edit.department_ids.length ? edit.department_ids : [],
        });
        state.message = 'Announcement updated.';
        cancelEdit();
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not update.';
    } finally {
        state.saving = false;
    }
}

async function removeRow(id) {
    if (!window.confirm('Delete this announcement?')) return;
    state.error = '';
    try {
        await window.axios.delete(`/api/announcements/${id}`);
        state.message = 'Deleted.';
        if (edit.id === id) cancelEdit();
        await load();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not delete.';
    }
}

function formatWhen(iso) {
    if (!iso) return 'Draft';
    try {
        return new Date(iso).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    } catch {
        return iso;
    }
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div>
            <h2 class="text-lg font-semibold text-white sm:text-xl">Announcements</h2>
        </div>

        <div v-if="state.message" class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">{{ state.message }}</div>
        <div v-if="state.error" class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">{{ state.error }}</div>

        <form class="space-y-3 rounded-2xl border border-white/10 bg-white/[0.03] p-4" @submit.prevent="createAnnouncement">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">New announcement</p>
            <input v-model="form.title" required placeholder="Title" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <textarea v-model="form.body" required rows="4" placeholder="Message" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white"></textarea>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-300">
                    <input v-model="form.is_pinned" type="checkbox" class="rounded border-white/20 bg-white/5">
                    Pin to top
                </label>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Publish at (optional)</label>
                    <input v-model="form.published_at" type="datetime-local" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="mb-1 block text-xs text-slate-500">Expires (optional)</label>
                    <input v-model="form.expires_at" type="datetime-local" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                </div>
            </div>
            <div>
                <p class="mb-2 text-xs text-slate-500">Audience (none = all staff)</p>
                <div class="flex flex-wrap gap-2">
                    <label v-for="d in state.departments" :key="d.id" class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-300">
                        <input type="checkbox" :checked="isDeptChecked(form.department_ids, d.id)" @change="toggleDept(form.department_ids, d.id)">
                        {{ d.name }}
                    </label>
                </div>
            </div>
            <button type="submit" :disabled="state.saving" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">Save</button>
        </form>

        <form v-if="edit.id" class="space-y-3 rounded-2xl border border-violet-500/25 bg-violet-500/5 p-4" @submit.prevent="saveEdit">
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-200">Editing #{{ edit.id }}</p>
                <button type="button" class="text-xs text-slate-400 hover:text-white" @click="cancelEdit">Cancel</button>
            </div>
            <input v-model="edit.title" required class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            <textarea v-model="edit.body" required rows="4" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white"></textarea>
            <label class="flex items-center gap-2 text-sm text-slate-300">
                <input v-model="edit.is_pinned" type="checkbox" class="rounded border-white/20 bg-white/5">
                Pin
            </label>
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="edit.published_at" type="datetime-local" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
                <input v-model="edit.expires_at" type="datetime-local" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white">
            </div>
            <div class="flex flex-wrap gap-2">
                <label v-for="d in state.departments" :key="'e'+d.id" class="flex cursor-pointer items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs text-slate-300">
                    <input type="checkbox" :checked="isDeptChecked(edit.department_ids, d.id)" @change="toggleDept(edit.department_ids, d.id)">
                    {{ d.name }}
                </label>
            </div>
            <button type="submit" :disabled="state.saving" class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50">Update</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
            <table class="min-w-full divide-y divide-white/10 text-left text-sm">
                <thead class="bg-white/5 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-2.5">Title</th>
                        <th class="px-4 py-2.5">Published</th>
                        <th class="px-4 py-2.5">Audience</th>
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                    <tr v-if="state.loading"><td colspan="4" class="px-4 py-10 text-center text-slate-500">Loading...</td></tr>
                    <tr v-else-if="!state.rows.length"><td colspan="4" class="px-4 py-10 text-center text-slate-500">No announcements yet.</td></tr>
                    <tr v-for="row in state.rows" :key="row.id">
                        <td class="px-4 py-3">
                            <span v-if="row.is_pinned" class="mr-1 text-amber-400">*</span>
                            <span class="font-medium text-white">{{ row.title }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ formatWhen(row.published_at) }}</td>
                        <td class="px-4 py-3 text-xs">{{ (row.target_audience?.department_ids || []).length ? row.target_audience.department_ids.join(', ') : 'All' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" class="mr-2 text-xs text-emerald-400 hover:text-emerald-300" @click="startEdit(row)">Edit</button>
                            <button type="button" class="text-xs text-red-300 hover:text-red-200" @click="removeRow(row.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
