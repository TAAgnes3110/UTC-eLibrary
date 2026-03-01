<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const props = defineProps({
    requests: { type: Object, required: true },
    faculties: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
    statusFilter: { type: String, default: 'pending' },
});

const fieldLabel = (f) => ({ code: 'Mã SV/CCCD', cohort: 'Khóa học', faculty_id: 'Khoa', department_id: 'Lớp' }[f] || f);
const statusLabel = (s) => ({ pending: 'Chờ duyệt', approved: 'Đã duyệt', rejected: 'Đã từ chối' }[s] || s);
const facultyName = (id) => props.faculties.find(f => f.id === id)?.name ?? id;
const departmentName = (id) => props.departments.find(d => d.id === id)?.name ?? id;

const displayValue = (req) => {
    if (req.field === 'faculty_id') {
        return { old: facultyName(Number(req.value_old)) || req.value_old, new: facultyName(Number(req.value_new)) || req.value_new };
    }
    if (req.field === 'department_id') {
        return { old: departmentName(Number(req.value_old)) || req.value_old || '—', new: departmentName(Number(req.value_new)) || req.value_new };
    }
    return { old: req.value_old || '—', new: req.value_new };
};

const loading = ref(false);
const approve = async (id) => {
    loading.value = true;
    try {
        await window.axios.post(`/profile-change-requests/${id}/approve`);
        router.reload();
    } finally {
        loading.value = false;
    }
};
const rejectForm = useForm({ admin_note: '' });
const rejectingId = ref(null);
const openReject = (id) => { rejectingId.value = id; rejectForm.reset(); };
const closeReject = () => { rejectingId.value = null; };
const submitReject = async (id) => {
    try {
        await window.axios.post(`/profile-change-requests/${id}/reject`, { admin_note: rejectForm.admin_note });
        closeReject();
        router.reload();
    } catch (e) {
        rejectForm.setErrors(e.response?.data?.data || {});
    }
};

const proofUrl = (path) => path ? `/storage/${path}` : null;
</script>

<template>
    <Head title="Duyệt yêu cầu chỉnh sửa thông tin - Admin" />
    <AdminLayout>
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">Duyệt yêu cầu chỉnh sửa thông tin</h1>
                <div class="flex gap-2">
                    <a
                        v-for="s in ['pending', 'approved', 'rejected']"
                        :key="s"
                        :href="route('admin.profile-change-requests.index', { status: s })"
                        :class="[statusFilter === s ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300']"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium"
                    >
                        {{ statusLabel(s) }}
                    </a>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="text-left py-3 px-4 font-medium text-slate-700 dark:text-slate-300">Bạn đọc</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-700 dark:text-slate-300">Loại</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-700 dark:text-slate-300">Từ → Sang</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-700 dark:text-slate-300">Minh chứng</th>
                                <th class="text-left py-3 px-4 font-medium text-slate-700 dark:text-slate-300">Trạng thái</th>
                                <th v-if="statusFilter === 'pending'" class="text-right py-3 px-4 font-medium text-slate-700 dark:text-slate-300">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="r in requests.data" :key="r.id" class="border-b border-slate-100 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                                <td class="py-3 px-4">
                                    <p class="font-medium text-slate-900 dark:text-white">{{ r.user?.name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ r.user?.email }}</p>
                                </td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ fieldLabel(r.field) }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">{{ displayValue(r).old }} → {{ displayValue(r).new }}</td>
                                <td class="py-3 px-4">
                                    <a v-if="proofUrl(r.proof_path)" :href="proofUrl(r.proof_path)" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">Xem</a>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="{
                                            'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-400': r.status === 'pending',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400': r.status === 'approved',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400': r.status === 'rejected',
                                        }"
                                        class="rounded px-2 py-0.5 text-xs font-medium"
                                    >
                                        {{ statusLabel(r.status) }}
                                    </span>
                                </td>
                                <td v-if="statusFilter === 'pending'" class="py-3 px-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <Button size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white" @click="approve(r.id)">Duyệt</Button>
                                        <Button size="sm" variant="outline" class="text-rose-600 border-rose-200 dark:border-rose-800" @click="openReject(r.id)">Từ chối</Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="!requests.data?.length" class="p-6 text-center text-slate-500 dark:text-slate-400 text-sm">Chưa có yêu cầu nào.</p>
                <div v-if="requests.data?.length && (requests.prev_page_url || requests.next_page_url)" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex justify-between items-center">
                    <a v-if="requests.prev_page_url" :href="requests.prev_page_url" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">← Trước</a>
                    <span v-else></span>
                    <span class="text-sm text-slate-500">Trang {{ requests.current_page }} / {{ requests.last_page }}</span>
                    <a v-if="requests.next_page_url" :href="requests.next_page_url" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">Sau →</a>
                    <span v-else></span>
                </div>
            </div>

            <!-- Modal Từ chối -->
            <Teleport to="body">
                <div v-if="rejectingId" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/50" @click="closeReject"></div>
                    <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-md p-6 shadow-xl border border-slate-200 dark:border-slate-800">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">Từ chối yêu cầu</h3>
                        <textarea v-model="rejectForm.admin_note" rows="3" class="w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-2 text-sm" placeholder="Lý do (tùy chọn)"></textarea>
                        <div class="flex justify-end gap-2 mt-4">
                            <Button variant="outline" @click="closeReject">Hủy</Button>
                            <Button class="bg-rose-600 hover:bg-rose-700 text-white" :disabled="rejectForm.processing" @click="submitReject(rejectingId)">Từ chối</Button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
