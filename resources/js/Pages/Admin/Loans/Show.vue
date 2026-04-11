<script setup>
import { onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { loansApi } from '@/api/loans';
import { toast } from '@/store/toast';

const props = defineProps({
    loanId: { type: Number, required: true },
});

const loading = ref(false);
const loan = ref(null);

function extractLoan(payload) {
    return payload?.data ?? null;
}

async function loadDetail() {
    loading.value = true;
    try {
        const res = await loansApi.get(props.loanId);
        loan.value = extractLoan(res);
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tải được chi tiết phiếu mượn.', { title: 'Lỗi' });
    } finally {
        loading.value = false;
    }
}

onMounted(loadDetail);
</script>

<template>
    <Head title="Chi tiết phiếu mượn" />
    <AdminLayout
        title="Phiếu mượn"
        :breadcrumbs="[
            { label: 'Phiếu mượn', href: route('admin.loans.index') },
            { label: 'Chi tiết phiếu' },
        ]"
    >
        <div v-if="loading" class="text-sm text-slate-500">Đang tải dữ liệu...</div>

        <div v-else-if="loan" class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4">
                <h2 class="text-base font-bold mb-3">Thông tin chính</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div><b>Mã phiếu:</b> {{ loan.loan_code || `#${loan.id}` }}</div>
                    <div><b>Mã thẻ:</b> {{ loan.library_card_number || '-' }}</div>
                    <div><b>Tên độc giả:</b> {{ loan.library_card_name || '-' }}</div>
                    <div><b>Người tạo:</b> {{ loan.created_by_name || '-' }}</div>
                    <div><b>Ngày mượn:</b> {{ loan.loan_date || '-' }}</div>
                    <div><b>Ngày hẹn trả:</b> {{ loan.due_date || '-' }}</div>
                    <div><b>Ngày trả:</b> {{ loan.return_date || 'Chưa trả' }}</div>
                    <div><b>Trạng thái:</b> {{ loan.status_label || loan.status }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 overflow-x-auto">
                <div class="p-4 font-bold">Danh sách sách mượn</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-4 py-2 text-left">Tên sách</th>
                            <th class="px-4 py-2 text-left">Số lượng</th>
                            <th class="px-4 py-2 text-left">Tình trạng khi mượn</th>
                            <th class="px-4 py-2 text-left">Tình trạng khi trả</th>
                            <th class="px-4 py-2 text-left">Tiền phạt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in loan.loan_items || []" :key="item.id" class="border-t border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-2">{{ item.book_title || '-' }}</td>
                            <td class="px-4 py-2">{{ item.quantity }}</td>
                            <td class="px-4 py-2">{{ item.condition_on_loan_label || item.condition_on_loan || '-' }}</td>
                            <td class="px-4 py-2">{{ item.condition_on_return || '-' }}</td>
                            <td class="px-4 py-2">{{ item.fine_amount ?? 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-2">
                <button class="admin-filter-btn px-3 py-2" @click="router.visit(route('admin.loans.edit', loan.id))">Sửa hạn trả</button>
                <button class="admin-filter-btn px-3 py-2" @click="router.visit(route('admin.loans.return', loan.id))">Trả sách</button>
                <button class="admin-filter-btn px-3 py-2" @click="router.visit(route('admin.loans.index'))">Quay lại</button>
            </div>
        </div>
    </AdminLayout>
</template>
