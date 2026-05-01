<script setup>
import { reactive, ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const profilePhoto = ref(null);
const profilePhotoPreview = ref('');

const form = reactive({
    employee_code: '',
    zk_badge_user_id: '',
    full_name: '',
    work_email: '',
    personal_email: '',
    phone: '',
    whatsapp_phone: '',
    cnic: '',
    date_of_birth: '',
    gender: '',
    address_line1: '',
    city: '',
    country: '',
    postal_code: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    emergency_contact_relation: '',
    bank_name: '',
    bank_branch: '',
    bank_account_title: '',
    bank_account_number: '',
    bank_iban: '',
    department_id: '',
    designation_id: '',
    manager_id: '',
    joining_date: '',
    employment_type: 'permanent',
    salary: '',
    status: 'active',
    notes: '',
    create_portal_login: false,
    portal_email: '',
    portal_password: '',
    portal_password_confirmation: '',
    portal_role: 'employee',
});

const opts = reactive({
    departments: [],
    designations: [],
    managers: [],
    loading: true,
    saving: false,
    errors: {},
    message: '',
});

async function loadDepartments() {
    const { data } = await window.axios.get('/api/departments');
    opts.departments = data.data;
}

async function loadDesignations() {
    const params = {};
    if (form.department_id) {
        params.department_id = form.department_id;
    }
    const { data } = await window.axios.get('/api/designations', { params });
    opts.designations = data.data;
}

async function loadManagers() {
    const { data } = await window.axios.get('/api/employees/managers');
    opts.managers = data.data;
}

watch(
    () => form.department_id,
    async () => {
        form.designation_id = '';
        await loadDesignations();
    }
);

onMounted(async () => {
    opts.loading = true;
    opts.message = '';
    try {
        await loadDepartments();
        await loadDesignations();
        await loadManagers();
    } catch (e) {
        opts.message = e.response?.data?.message ?? 'Could not load form options.';
    } finally {
        opts.loading = false;
    }
});

function fieldError(key) {
    return opts.errors[key]?.[0] ?? '';
}

function onProfilePhotoChange(event) {
    const file = event.target.files?.[0];
    profilePhoto.value = file ?? null;
    if (profilePhotoPreview.value) {
        URL.revokeObjectURL(profilePhotoPreview.value);
    }
    profilePhotoPreview.value = file ? URL.createObjectURL(file) : '';
}

function appendFormData(fd, key, value) {
    if (value === '' || value === null || value === undefined) {
        return;
    }
    fd.append(key, value);
}

function appendAlways(fd, key, value) {
    fd.append(key, value === null || value === undefined ? '' : String(value));
}

function buildFormData() {
    const fd = new FormData();
    fd.append('employee_code', form.employee_code);
    fd.append('full_name', form.full_name);
    fd.append('status', form.status);
    fd.append('employment_type', form.employment_type);
    appendAlways(fd, 'work_email', form.work_email);
    appendFormData(fd, 'personal_email', form.personal_email);
    appendAlways(fd, 'phone', form.phone);
    appendAlways(fd, 'whatsapp_phone', form.whatsapp_phone);
    appendAlways(fd, 'cnic', form.cnic);
    appendAlways(fd, 'date_of_birth', form.date_of_birth);
    appendFormData(fd, 'gender', form.gender);
    appendAlways(fd, 'address_line1', form.address_line1);
    appendFormData(fd, 'city', form.city);
    appendFormData(fd, 'country', form.country);
    appendFormData(fd, 'postal_code', form.postal_code);
    appendAlways(fd, 'emergency_contact_name', form.emergency_contact_name);
    appendAlways(fd, 'emergency_contact_phone', form.emergency_contact_phone);
    appendAlways(fd, 'emergency_contact_relation', form.emergency_contact_relation);
    appendAlways(fd, 'bank_name', form.bank_name);
    appendAlways(fd, 'bank_branch', form.bank_branch);
    appendAlways(fd, 'bank_account_title', form.bank_account_title);
    appendAlways(fd, 'bank_account_number', form.bank_account_number);
    appendAlways(fd, 'bank_iban', form.bank_iban);
    appendFormData(fd, 'joining_date', form.joining_date);
    appendFormData(fd, 'salary', form.salary);
    appendFormData(fd, 'notes', form.notes);
    appendFormData(fd, 'zk_badge_user_id', form.zk_badge_user_id);
    appendFormData(fd, 'department_id', form.department_id);
    appendFormData(fd, 'designation_id', form.designation_id);
    appendFormData(fd, 'manager_id', form.manager_id);
    if (profilePhoto.value) {
        fd.append('profile_photo', profilePhoto.value);
    }
    fd.append('create_portal_login', form.create_portal_login ? '1' : '0');
    if (form.create_portal_login) {
        fd.append('portal_email', form.portal_email);
        fd.append('portal_password', form.portal_password);
        fd.append('portal_password_confirmation', form.portal_password_confirmation);
        fd.append('portal_role', form.portal_role);
    }
    return fd;
}

async function submit() {
    opts.saving = true;
    opts.errors = {};
    opts.message = '';
    try {
        await window.axios.post('/api/employees', buildFormData());
        await router.push('/employees');
    } catch (e) {
        if (e.response?.status === 422) {
            opts.errors = e.response.data.errors ?? {};
        } else {
            opts.message = e.response?.data?.message ?? 'Could not save employee.';
        }
    } finally {
        opts.saving = false;
    }
}
</script>

<template>
    <div class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-white sm:text-xl">Add employee</h2>
                <p class="mt-1 text-sm text-slate-500">Contact, emergency, and bank details are required (same as the information form).</p>
            </div>
            <button
                type="button"
                class="rounded-xl border border-white/10 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-white/5"
                @click="router.push('/employees')"
            >
                Cancel
            </button>
        </div>

        <div
            v-if="opts.message"
            class="rounded-xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"
        >
            {{ opts.message }}
        </div>

        <form
            v-if="!opts.loading"
            class="space-y-8 rounded-2xl border border-white/10 bg-white/[0.03] p-6 sm:p-8"
            @submit.prevent="submit"
        >
            <section class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Identity &amp; job</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Employee code *</label>
                        <input
                            v-model="form.employee_code"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="e.g. EMP-001"
                        >
                        <p v-if="fieldError('employee_code')" class="mt-1 text-xs text-red-300">{{ fieldError('employee_code') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">ZKTeco user ID (device)</label>
                        <input
                            v-model="form.zk_badge_user_id"
                            type="number"
                            min="1"
                            step="1"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Optional — same ID as on K50"
                        >
                        <p class="mt-1 text-xs text-slate-500">Used when syncing punches from the LAN device.</p>
                        <p v-if="fieldError('zk_badge_user_id')" class="mt-1 text-xs text-red-300">{{ fieldError('zk_badge_user_id') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Full name (as per CNIC) *</label>
                        <input
                            v-model="form.full_name"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('full_name')" class="mt-1 text-xs text-red-300">{{ fieldError('full_name') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Profile photo</label>
                        <div class="flex flex-wrap items-center gap-4">
                            <div
                                class="flex h-20 w-20 shrink-0 overflow-hidden rounded-full border border-white/15 bg-white/5 ring-2 ring-white/5"
                            >
                                <img
                                    v-if="profilePhotoPreview"
                                    :src="profilePhotoPreview"
                                    alt=""
                                    class="h-full w-full object-cover"
                                >
                                <div
                                    v-else
                                    class="flex h-full w-full items-center justify-center px-1 text-center text-[10px] text-slate-500"
                                >
                                    No photo
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <input
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                                    class="block w-full max-w-xs text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-emerald-500"
                                    @change="onProfilePhotoChange"
                                >
                                <p class="mt-1 text-xs text-slate-500">JPG / PNG / WebP / GIF, max ~2 MB (optional)</p>
                                <p v-if="fieldError('profile_photo')" class="mt-1 text-xs text-red-300">
                                    {{ fieldError('profile_photo') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Department</label>
                        <select
                            v-model="form.department_id"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">— Select —</option>
                            <option v-for="d in opts.departments" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Designation</label>
                        <select
                            v-model="form.designation_id"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">— Select —</option>
                            <option v-for="d in opts.designations" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Reporting manager</label>
                        <select
                            v-model="form.manager_id"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">— None —</option>
                            <option v-for="m in opts.managers" :key="m.id" :value="String(m.id)">
                                {{ m.full_name }} ({{ m.employee_code }})
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Joining date</label>
                        <input
                            v-model="form.joining_date"
                            type="date"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Employment type</label>
                        <select
                            v-model="form.employment_type"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="permanent">Permanent</option>
                            <option value="contract">Contract</option>
                            <option value="intern">Intern</option>
                            <option value="consultant">Consultant</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Monthly salary (PKR)</label>
                        <input
                            v-model="form.salary"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="e.g. 150000"
                        >
                        <p v-if="fieldError('salary')" class="mt-1 text-xs text-red-300">{{ fieldError('salary') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Status *</label>
                        <select
                            v-model="form.status"
                            required
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="active">Active</option>
                            <option value="on_leave">On leave</option>
                            <option value="resigned">Resigned</option>
                            <option value="terminated">Terminated</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Contact &amp; CNIC (required)</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Work email *</label>
                        <input
                            v-model="form.work_email"
                            type="email"
                            required
                            autocomplete="email"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="company email"
                        >
                        <p v-if="fieldError('work_email')" class="mt-1 text-xs text-red-300">{{ fieldError('work_email') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Personal email</label>
                        <input
                            v-model="form.personal_email"
                            type="email"
                            autocomplete="email"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Gmail / Yahoo / Hotmail (optional)"
                        >
                        <p v-if="fieldError('personal_email')" class="mt-1 text-xs text-red-300">{{ fieldError('personal_email') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">CNIC # *</label>
                        <input
                            v-model="form.cnic"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="e.g. 35202-1234567-1"
                        >
                        <p v-if="fieldError('cnic')" class="mt-1 text-xs text-red-300">{{ fieldError('cnic') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Date of birth *</label>
                        <input
                            v-model="form.date_of_birth"
                            type="date"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('date_of_birth')" class="mt-1 text-xs text-red-300">{{ fieldError('date_of_birth') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Contact number *</label>
                        <input
                            v-model="form.phone"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Mobile"
                        >
                        <p v-if="fieldError('phone')" class="mt-1 text-xs text-red-300">{{ fieldError('phone') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">WhatsApp number *</label>
                        <input
                            v-model="form.whatsapp_phone"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Same or different number"
                        >
                        <p v-if="fieldError('whatsapp_phone')" class="mt-1 text-xs text-red-300">{{ fieldError('whatsapp_phone') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Gender</label>
                        <select
                            v-model="form.gender"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="">—</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Address &amp; emergency (required)</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Current resident address *</label>
                        <input
                            v-model="form.address_line1"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('address_line1')" class="mt-1 text-xs text-red-300">{{ fieldError('address_line1') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">City</label>
                        <input
                            v-model="form.city"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Country</label>
                        <input
                            v-model="form.country"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Emergency contact number *</label>
                        <input
                            v-model="form.emergency_contact_phone"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('emergency_contact_phone')" class="mt-1 text-xs text-red-300">{{ fieldError('emergency_contact_phone') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Emergency contact person name *</label>
                        <input
                            v-model="form.emergency_contact_name"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('emergency_contact_name')" class="mt-1 text-xs text-red-300">{{ fieldError('emergency_contact_name') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Relation to emergency contact *</label>
                        <input
                            v-model="form.emergency_contact_relation"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="e.g. Father, spouse"
                        >
                        <p v-if="fieldError('emergency_contact_relation')" class="mt-1 text-xs text-red-300">{{ fieldError('emergency_contact_relation') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Notes</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Bank details (required)</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Bank name *</label>
                        <input
                            v-model="form.bank_name"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('bank_name')" class="mt-1 text-xs text-red-300">{{ fieldError('bank_name') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Branch *</label>
                        <input
                            v-model="form.bank_branch"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('bank_branch')" class="mt-1 text-xs text-red-300">{{ fieldError('bank_branch') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Account title *</label>
                        <input
                            v-model="form.bank_account_title"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('bank_account_title')" class="mt-1 text-xs text-red-300">{{ fieldError('bank_account_title') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Account number *</label>
                        <input
                            v-model="form.bank_account_number"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                        <p v-if="fieldError('bank_account_number')" class="mt-1 text-xs text-red-300">{{ fieldError('bank_account_number') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">IBAN # *</label>
                        <input
                            v-model="form.bank_iban"
                            required
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm font-mono text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="PK…"
                            autocapitalize="characters"
                        >
                        <p v-if="fieldError('bank_iban')" class="mt-1 text-xs text-red-300">{{ fieldError('bank_iban') }}</p>
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Dashboard login (optional)</h3>
                <p class="text-xs text-slate-500">
                    Create a user account so this employee can sign in with email and password. Role controls what they can access.
                </p>
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-white/[0.02] px-4 py-3">
                    <input v-model="form.create_portal_login" type="checkbox" class="h-4 w-4 rounded border-white/20 bg-white/5 text-emerald-600 focus:ring-emerald-500/40">
                    <span class="text-sm text-slate-300">Create portal login for this employee</span>
                </label>
                <div v-if="form.create_portal_login" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Login email *</label>
                        <input
                            v-model="form.portal_email"
                            type="email"
                            autocomplete="off"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="name@company.com"
                        >
                        <p v-if="fieldError('portal_email')" class="mt-1 text-xs text-red-300">{{ fieldError('portal_email') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Password *</label>
                        <input
                            v-model="form.portal_password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                            placeholder="Min. 8 characters"
                        >
                        <p v-if="fieldError('portal_password')" class="mt-1 text-xs text-red-300">{{ fieldError('portal_password') }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Confirm password *</label>
                        <input
                            v-model="form.portal_password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border border-white/10 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-slate-300">Role *</label>
                        <select
                            v-model="form.portal_role"
                            class="w-full rounded-xl border border-white/10 bg-slate-800/90 px-4 py-2.5 text-sm text-slate-100 focus:border-emerald-500/40 focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                        >
                            <option value="employee">Employee (self-service: leave, etc.)</option>
                            <option value="hr">HR (manage employees, attendance, leave…)</option>
                            <option value="admin">Admin (full access)</option>
                        </select>
                        <p v-if="fieldError('portal_role')" class="mt-1 text-xs text-red-300">{{ fieldError('portal_role') }}</p>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-3 border-t border-white/10 pt-6">
                <button
                    type="button"
                    class="rounded-xl border border-white/10 px-5 py-2.5 text-sm font-medium text-slate-300 hover:bg-white/5"
                    @click="router.push('/employees')"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-900/30 hover:bg-emerald-500 disabled:opacity-50"
                    :disabled="opts.saving"
                >
                    {{ opts.saving ? 'Saving…' : 'Save employee' }}
                </button>
            </div>
        </form>

        <div v-else class="rounded-2xl border border-white/10 bg-white/[0.03] px-6 py-16 text-center text-slate-500">
            Loading form options…
        </div>
    </div>
</template>
