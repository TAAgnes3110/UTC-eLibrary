<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { loansApi } from '@/api/loans';
import { toast } from '@/store/toast';

const props = defineProps({
    loanId: { type: Number, required: true },
});

const loading = ref(false);
const saving = ref(false);
const loan = ref(null);
const form = reactive({
    due_date: '',
});

async function loadDetail() {
    loading.value = true;
    try {
        const res = await loansApi.get(props.loanId);
        loan.value = res?.data ?? null;
        form.due_date = loan.value?.due_date ? String(loan.value.due_date).slice(0, 10) : '';
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tải được dữ liệu phiếu.', { title: 'Lỗi' });
    } finally {
        loading.value = false;
    }
}

async function saveDueDate() {
    if (!form.due_date) {
        toast.warn('Vui lòng nhập ngày hẹn trả.');
        return;
    }
    saving.value = true;
    try {
        await loansApi.update(props.loanId, { due_date: form.due_date });
        toast.success('Cập nhật ngày hẹn trả thành công.', { title: 'Thành công' });
        router.visit(route('admin.loans.show', props.loanId));
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không cập nhật được phiếu.', { title: 'Lỗi' });
    } finally {
        saving.value = false;
    }
}

onMounted(loadDetail);
</script>

<template>
    <Head title="Sửa phiếu mượn" />
    <AdminLayout
        title="Phiếu mượn"
        :breadcrumbs="[
            { label: 'Phiếu mượn', href: route('admin.loans.index') },
            { label: 'Sửa phiếu' },
        ]"
    >
        <div v-if="loading" class="text-sm text-slate-500">Đang tải dữ liệu...</div>

        <div v-else class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4">
                <h2 class="text-base font-bold mb-3">Chỉnh sửa phiếu mượn</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <div class="text-sm"><b>Mã phiếu:</b> {{ loan?.loan_code || `#${loan?.id}` }}</div>
                        <div class="text-sm"><b>Độc giả:</b> {{ loan?.library_card_name || '-' }}</div>
                    </div>
                    <label class="space-y-1">
                        <span class="text-sm font-medium">Ngày hẹn trả mới</span>
                        <input v-model="form.due_date" type="date" class="admin-filter-input w-full" />
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button class="admin-filter-btn px-4 py-2.5 min-h-[44px]" :disabled="saving" @click="saveDueDate">
                    {{ saving ? 'Đang lưu...' : 'Lưu thay đổi' }}
                </button>
                <button class="admin-filter-btn px-4 py-2.5 min-h-[44px]" @click="router.visit(route('admin.loans.show', props.loanId))">
                    Hủy
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
