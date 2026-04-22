<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button } from '@/Components/ui/button';
import { classificationsApi } from '@/api/classifications';
import { classificationDetailsApi } from '@/api/classificationDetails';
import { toast } from '@/store/toast';

const loading = ref(false);
const loadingDetails = ref(false);
const classifications = ref([]);
const details = ref([]);
const selectedClassificationId = ref('');

const classificationForm = ref({ id: null, code: '', name: '' });
const detailForm = ref({ id: null, classification_id: '', code: '', name: '' });

const stats = computed(() => ({
    classifications: classifications.value.length,
    details: details.value.length,
    filteredDetails: detailsOfSelected.value.length,
}));

const detailsOfSelected = computed(() => {
    if (!selectedClassificationId.value) return details.value;
    return details.value.filter((d) => String(d.classification_id) === String(selectedClassificationId.value));
});

function resetClassificationForm() {
    classificationForm.value = { id: null, code: '', name: '' };
}

function resetDetailForm() {
    detailForm.value = { id: null, classification_id: selectedClassificationId.value || '', code: '', name: '' };
}

function editClassification(item) {
    classificationForm.value = { id: item.id, code: item.code || '', name: item.name || '' };
}

function editDetail(item) {
    detailForm.value = {
        id: item.id,
        classification_id: String(item.classification_id || ''),
        code: item.code || '',
        name: item.name || '',
    };
}

async function loadClassifications() {
    loading.value = true;
    try {
        const payload = await classificationsApi.list({ per_page: 500 });
        classifications.value = payload?.data?.data || [];
    } catch {
        toast.error('Không thể tải phân loại.');
    } finally {
        loading.value = false;
    }
}

async function loadDetails() {
    loadingDetails.value = true;
    try {
        const payload = await classificationDetailsApi.list({
            per_page: 1000,
            classification_id: selectedClassificationId.value || undefined,
        });
        details.value = payload?.data?.data || [];
    } catch {
        toast.error('Không thể tải phân loại chi tiết.');
    } finally {
        loadingDetails.value = false;
    }
}

async function saveClassification() {
    const payload = {
        code: classificationForm.value.code,
        name: classificationForm.value.name,
    };
    if (!payload.code || !payload.name) {
        toast.error('Vui lòng nhập mã và tên phân loại.');
        return;
    }
    try {
        if (classificationForm.value.id) {
            await classificationsApi.update(classificationForm.value.id, payload);
            toast.success('Đã cập nhật phân loại.');
        } else {
            await classificationsApi.create(payload);
            toast.success('Đã tạo phân loại.');
        }
        resetClassificationForm();
        await loadClassifications();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể lưu phân loại.');
    }
}

async function saveDetail() {
    const payload = {
        classification_id: Number(detailForm.value.classification_id || selectedClassificationId.value),
        code: detailForm.value.code,
        name: detailForm.value.name,
    };
    if (!payload.classification_id || !payload.code || !payload.name) {
        toast.error('Vui lòng nhập đủ phân loại chính, mã và tên chi tiết.');
        return;
    }
    try {
        if (detailForm.value.id) {
            await classificationDetailsApi.update(detailForm.value.id, payload);
            toast.success('Đã cập nhật phân loại chi tiết.');
        } else {
            await classificationDetailsApi.create(payload);
            toast.success('Đã tạo phân loại chi tiết.');
        }
        resetDetailForm();
        await loadDetails();
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không thể lưu phân loại chi tiết.');
    }
}

async function removeClassification(item) {
    if (!confirm(`Xóa phân loại "${item.name}"?`)) return;
    try {
        await classificationsApi.remove(item.id);
        toast.success('Đã xóa phân loại.');
        if (String(selectedClassificationId.value) === String(item.id)) {
            selectedClassificationId.value = '';
        }
        await Promise.all([loadClassifications(), loadDetails()]);
    } catch {
        toast.error('Không thể xóa phân loại.');
    }
}

async function removeDetail(item) {
    if (!confirm(`Xóa phân loại chi tiết "${item.name}"?`)) return;
    try {
        await classificationDetailsApi.remove(item.id);
        toast.success('Đã xóa phân loại chi tiết.');
        await loadDetails();
    } catch {
        toast.error('Không thể xóa phân loại chi tiết.');
    }
}

onMounted(async () => {
    await Promise.all([loadClassifications(), loadDetails()]);
});
</script>

<template>
    <Head title="Phân loại sách - Admin" />
    <AdminLayout
        title="Phân loại sách"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Cấu hình thư viện' }, { label: 'Phân loại sách' }]"
    >
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Số phân loại chính</p>
                    <p class="text-2xl font-semibold text-blue-600">{{ stats.classifications }}</p>
                </div>
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Số phân loại chi tiết</p>
                    <p class="text-2xl font-semibold text-indigo-600">{{ stats.details }}</p>
                </div>
                <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                    <p class="text-xs text-slate-500">Chi tiết đang hiển thị</p>
                    <p class="text-2xl font-semibold text-emerald-600">{{ stats.filteredDetails }}</p>
                </div>
            </div>

            <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                <p class="font-semibold mb-3">{{ classificationForm.id ? 'Sửa phân loại' : 'Thêm phân loại' }}</p>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <input v-model="classificationForm.code" class="admin-filter-select" placeholder="Mã phân loại" />
                    <input v-model="classificationForm.name" class="admin-filter-select" placeholder="Tên phân loại" />
                    <Button @click="saveClassification">Lưu phân loại</Button>
                    <Button variant="outline" @click="resetClassificationForm">Hủy</Button>
                </div>
            </div>

            <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800 overflow-x-auto">
                <p class="font-semibold mb-3">Danh sách phân loại</p>
                <table class="w-full min-w-[700px] text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Mã</th>
                            <th class="py-2">Tên</th>
                            <th class="py-2">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in classifications" :key="item.id" class="border-b">
                            <td class="py-2">{{ item.code }}</td>
                            <td class="py-2">{{ item.name }}</td>
                            <td class="py-2 flex gap-2">
                                <Button variant="outline" size="sm" @click="selectedClassificationId = String(item.id); loadDetails()">Chi tiết</Button>
                                <Button variant="outline" size="sm" @click="editClassification(item)">Sửa</Button>
                                <Button variant="destructive" size="sm" @click="removeClassification(item)">Xóa</Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="loading" class="text-slate-500 mt-2">Đang tải...</p>
            </div>

            <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800">
                <p class="font-semibold mb-3">{{ detailForm.id ? 'Sửa phân loại chi tiết' : 'Thêm phân loại chi tiết' }}</p>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    <select v-model="detailForm.classification_id" class="admin-filter-select">
                        <option value="">Phân loại chính</option>
                        <option v-for="item in classifications" :key="item.id" :value="String(item.id)">
                            {{ item.code }} - {{ item.name }}
                        </option>
                    </select>
                    <input v-model="detailForm.code" class="admin-filter-select" placeholder="Mã chi tiết" />
                    <input v-model="detailForm.name" class="admin-filter-select" placeholder="Tên chi tiết" />
                    <Button @click="saveDetail">Lưu chi tiết</Button>
                    <Button variant="outline" @click="resetDetailForm">Hủy</Button>
                </div>
            </div>

            <div class="rounded-xl border p-4 bg-white dark:bg-slate-900 dark:border-slate-800 overflow-x-auto">
                <div class="flex items-center justify-between mb-3">
                    <p class="font-semibold">Danh sách phân loại chi tiết</p>
                    <select v-model="selectedClassificationId" class="admin-filter-select w-auto" @change="loadDetails">
                        <option value="">Tất cả phân loại</option>
                        <option v-for="item in classifications" :key="item.id" :value="String(item.id)">
                            {{ item.code }} - {{ item.name }}
                        </option>
                    </select>
                </div>
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Mã</th>
                            <th class="py-2">Tên</th>
                            <th class="py-2">Phân loại chính</th>
                            <th class="py-2">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in detailsOfSelected" :key="item.id" class="border-b">
                            <td class="py-2">{{ item.code }}</td>
                            <td class="py-2">{{ item.name }}</td>
                            <td class="py-2">{{ item.classification?.code }} - {{ item.classification?.name }}</td>
                            <td class="py-2 flex gap-2">
                                <Button variant="outline" size="sm" @click="editDetail(item)">Sửa</Button>
                                <Button variant="destructive" size="sm" @click="removeDetail(item)">Xóa</Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="loadingDetails" class="text-slate-500 mt-2">Đang tải...</p>
            </div>
        </div>
    </AdminLayout>
</template>
