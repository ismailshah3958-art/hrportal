<script setup>
import { reactive, onMounted, computed, watch, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const state = reactive({
    row: null,
    loading: true,
    error: '',
    documents: [],
    documentsLoading: false,
    documentsError: '',
    docUploading: false,
});

const docForm = reactive({
    category: 'other',
    title: '',
    files: [],
});

const docFileInputRef = ref(null);

const pkMoney = new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR',
    maximumFractionDigits: 0,
});

function display(v) {
    if (v === null || v === undefined || v === '') {
        return '—';
    }
    return String(v);
}

function formatSalary(val) {
    if (val === null || val === undefined || val === '') {
        return '—';
    }
    const n = Number(val);
    if (Number.isNaN(n)) {
        return String(val);
    }
    return pkMoney.format(n);
}

function formatIso(val) {
    if (!val) {
        return '—';
    }
    try {
        return new Date(val).toLocaleString(undefined, {
            dateStyle: 'medium',
            timeStyle: 'short',
        });
    } catch {
        return String(val);
    }
}

function humanize(val) {
    if (!val) {
        return '—';
    }
    return String(val).replaceAll('_', ' ');
}

const salaryLabel = computed(() => formatSalary(state.row?.salary));

function statusClass(status) {
    const map = {
        active: 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-500/25',
        on_leave: 'bg-amber-500/15 text-amber-200 ring-1 ring-amber-500/25',
        resigned: 'bg-slate-500/20 text-slate-300 ring-1 ring-white/10',
        terminated: 'bg-red-500/15 text-red-200 ring-1 ring-red-500/25',
    };
    return map[status] ?? map.active;
}

async function loadDocuments() {
    state.documentsLoading = true;
    state.documentsError = '';
    state.documents = [];
    try {
        const { data } = await window.axios.get(`/api/employees/${route.params.id}/documents`);
        state.documents = data.data ?? [];
    } catch (e) {
        state.documentsError = e.response?.data?.message ?? '';
    } finally {
        state.documentsLoading = false;
    }
}

async function load() {
    state.loading = true;
    state.error = '';
    try {
        const { data } = await window.axios.get(`/api/employees/${route.params.id}`);
        state.row = data.data;
        await loadDocuments();
    } catch (e) {
        state.error = e.response?.data?.message ?? 'Could not load employee.';
        state.row = null;
    } finally {
        state.loading = false;
    }
}

function onDocFileChange(e) {
    const list = e.target.files;
    docForm.files = list && list.length ? Array.from(list) : [];
}

async function uploadDocument() {
    if (!docForm.files.length) {
        return;
    }
    state.docUploading = true;
    state.documentsError = '';
    try {
        const fd = new FormData();
        fd.append('category', docForm.category);
        if (docForm.files.length === 1) {
            fd.append('title', docForm.title || '');
        }
        docForm.files.forEach((file) => {
            fd.append('files[]', file);
        });
        await window.axios.post(`/api/employees/${route.params.id}/documents`, fd);
        docForm.title = '';
        docForm.files = [];
        docForm.category = 'other';
        if (docFileInputRef.value) {
            docFileInputRef.value.value = '';
        }
        await loadDocuments();
    } catch (e) {
        state.documentsError = e.response?.data?.message ?? 'Upload failed.';
    } finally {
        state.docUploading = false;
    }
}

async function deleteDocument(doc) {
    if (!window.confirm('Delete this file?')) return;
    state.documentsError = '';
    try {
        await window.axios.delete(`/api/employees/${route.params.id}/documents/${doc.id}`);
        await loadDocuments();
    } catch (e) {
        state.documentsError = e.response?.data?.message ?? 'Delete failed.';
    }
}

onMounted(load);

watch(
    () => route.params.id,
    () => load()
);

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
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Employee profile</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Complete record for every stored field. Use Edit to change data.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-white/5"
                    @click="router.push('/employees')"
                >
                    Back to list
                </button>
                <button
                    v-if="state.row"
                    type="button"
                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-900/30 hover:bg-emerald-500"
                    @click="router.push(`/employees/${state.row.id}/edit`)"
                >
                    Edit
                </button>
            </div>
        </div>

        <div
            v-if="state.error"
            class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"
        >
            {{ state.error }}
        </div>

        <div v-if="state.loading" class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-slate-500">
            Loading…
        </div>

        <div
            v-else-if="state.row"
            class="space-y-6"
        >
            <!-- Header card -->
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <div class="flex flex-col gap-6 border-b border-white/10 p-6 sm:flex-row sm:items-center sm:gap-8">
                    <div
                        class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/15 bg-white/5 text-2xl font-semibold text-slate-300"
                    >
                        <img
                            v-if="state.row.profile_photo_url"
                            :src="state.row.profile_photo_url"
                            alt=""
                            class="h-full w-full object-cover"
                        >
                        <span v-else>{{ initials(state.row.full_name) }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-mono text-xs text-emerald-200/90">{{ display(state.row.employee_code) }}</p>
                        <h3 class="mt-1 text-xl font-semibold text-white">{{ display(state.row.full_name) }}</h3>
                        <p class="mt-1 text-sm text-slate-400">
                            {{ display(state.row.designation?.name) }}
                            <span v-if="state.row.department?.name" class="text-slate-500"> · {{ state.row.department.name }}</span>
                        </p>
                        <p class="mt-3">
                            <span
                                class="inline-flex rounded-lg px-2 py-0.5 text-xs font-medium capitalize"
                                :class="statusClass(state.row.status)"
                            >
                                {{ humanize(state.row.status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- File attachments (placed high so HR sees uploads without scrolling past every field) -->
            <section id="employee-files" class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    File attachments
                </h3>
                <div class="space-y-4 p-6">
                    <div v-if="state.documentsError" class="rounded-lg border border-red-500/30 bg-red-500/10 px-3 py-2 text-xs text-red-200">{{ state.documentsError }}</div>

                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="uploadDocument">
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Category</label>
                            <select v-model="docForm.category" class="rounded-lg border border-white/10 bg-white/5 px-2 py-2 text-xs text-white">
                                <option value="cv">CV</option>
                                <option value="contract">Contract</option>
                                <option value="id_copy">ID copy</option>
                                <option value="medical">Medical</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="min-w-[8rem] flex-1">
                            <label class="mb-1 block text-xs text-slate-500">Title (optional, single file)</label>
                            <input v-model="docForm.title" type="text" :disabled="docForm.files.length > 1" class="w-full rounded-lg border border-white/10 bg-white/5 px-2 py-2 text-xs text-white disabled:opacity-40">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-slate-500">Files</label>
                            <input ref="docFileInputRef" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="block text-xs text-slate-300" @change="onDocFileChange">
                            <p v-if="docForm.files.length" class="mt-1 text-[11px] text-slate-500">{{ docForm.files.length }} selected</p>
                        </div>
                        <button type="submit" :disabled="state.docUploading || !docForm.files.length" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-40">Upload</button>
                    </form>

                    <div v-if="state.documentsLoading" class="text-xs text-slate-500">Loading files…</div>
                    <table v-else class="min-w-full text-left text-xs">
                        <thead class="text-slate-500">
                            <tr>
                                <th class="py-2 pr-2">Category</th>
                                <th class="py-2 pr-2">Name</th>
                                <th class="py-2 pr-2">Size</th>
                                <th class="py-2 pr-2">Added</th>
                                <th class="py-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-300">
                            <tr v-if="!state.documents.length">
                                <td colspan="5" class="py-4 text-slate-500">No files yet.</td>
                            </tr>
                            <tr v-for="d in state.documents" :key="d.id" class="border-t border-white/5">
                                <td class="py-2 pr-2 capitalize">{{ String(d.category || '').replaceAll('_', ' ') }}</td>
                                <td class="py-2 pr-2">{{ d.title || d.original_filename || '—' }}</td>
                                <td class="py-2 pr-2 font-mono">{{ d.size_bytes != null ? `${Math.round(d.size_bytes / 1024)} KB` : '—' }}</td>
                                <td class="py-2 pr-2">{{ formatIso(d.created_at) }}</td>
                                <td class="py-2 text-right">
                                    <a :href="d.download_url" class="mr-2 text-emerald-400 hover:text-emerald-300">Download</a>
                                    <button type="button" class="text-red-300 hover:text-red-200" @click="deleteDocument(d)">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- System & identifiers -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    System &amp; identifiers
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Record ID</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ display(state.row.id) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Employee code</dt>
                        <dd class="mt-1 font-mono text-sm text-emerald-200/90">{{ display(state.row.employee_code) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Linked user ID</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ display(state.row.user_id) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">ZKTeco device user ID</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-200">{{ display(state.row.zk_badge_user_id) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Profile photo URL</dt>
                        <dd class="mt-1 break-all text-sm text-slate-300">{{ display(state.row.profile_photo_url) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Created at</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ formatIso(state.row.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Updated at</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ formatIso(state.row.updated_at) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Identity -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Identity
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Full name</dt>
                        <dd class="mt-1 text-sm font-medium text-white">{{ display(state.row.full_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Date of birth</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.date_of_birth) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Gender</dt>
                        <dd class="mt-1 text-sm capitalize text-slate-300">{{ humanize(state.row.gender) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Organization & job -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Organization &amp; job
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Department ID</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.department?.id) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Department name</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.department?.name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Designation ID</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.designation?.id) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Designation name</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.designation?.name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manager employee ID</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.manager?.id) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manager name</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.manager?.full_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Manager employee code</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.manager?.employee_code) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Joining date</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.joining_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Exit date</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.exit_date) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Employment type</dt>
                        <dd class="mt-1 text-sm capitalize text-slate-300">{{ humanize(state.row.employment_type) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Employment status</dt>
                        <dd class="mt-1 text-sm capitalize text-slate-300">{{ humanize(state.row.status) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Compensation -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Compensation
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly salary (raw)</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.salary) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Monthly salary (PKR)</dt>
                        <dd class="mt-1 text-sm font-medium text-white">{{ salaryLabel }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Bank -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Bank details
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Bank name</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.bank_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Branch</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.bank_branch) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Account title</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.bank_account_title) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Account number</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.bank_account_number) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">IBAN</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.bank_iban) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Contact & documents -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Contact &amp; documents
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Work email</dt>
                        <dd class="mt-1 break-all text-sm text-slate-300">{{ display(state.row.work_email) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Personal email</dt>
                        <dd class="mt-1 break-all text-sm text-slate-300">{{ display(state.row.personal_email) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Phone</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.phone) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">WhatsApp</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.whatsapp_phone) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">CNIC</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.cnic) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Address (each line) -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Address
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Address line 1</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.address_line1) }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Address line 2</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.address_line2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">City</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.city) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Country</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.country) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Postal code</dt>
                        <dd class="mt-1 font-mono text-sm text-slate-300">{{ display(state.row.postal_code) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Emergency -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Emergency contact
                </h3>
                <dl class="grid gap-5 p-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Emergency contact name</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.emergency_contact_name) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Emergency contact phone</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.emergency_contact_phone) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Relation</dt>
                        <dd class="mt-1 text-sm text-slate-300">{{ display(state.row.emergency_contact_relation) }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Notes -->
            <section class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03]">
                <h3 class="border-b border-white/10 bg-white/5 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Notes
                </h3>
                <div class="p-6">
                    <p class="whitespace-pre-wrap text-sm text-slate-300">{{ display(state.row.notes) }}</p>
                </div>
            </section>
        </div>
    </div>
</template>
