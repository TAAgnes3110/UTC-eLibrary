<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
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
    return_date: new Date().toISOString().slice(0, 10),
    returns: {},
});

const conditions = [
    { value: 'tot', label: 'Sách còn tốt' },
    { value: 'hong', label: 'Sách hư hỏng' },
    { value: 'mat', label: 'Sách bị mất' },
];

function daysBorrowed(loanDate, returnDate) {
    if (!loanDate || !returnDate) return 0;
    const a = new Date(loanDate);
    const b = new Date(returnDate);
    const ms = b.setHours(0, 0, 0, 0) - a.setHours(0, 0, 0, 0);
    return Math.max(0, Math.floor(ms / 86400000));
}

const borrowedDays = computed(() => daysBorrowed(loan.value?.loan_date, form.return_date));

async function loadDetail() {
    loading.value = true;
    try {
        const res = await loansApi.get(props.loanId);
        loan.value = res?.data ?? null;

        form.returns = {};
        (loan.value?.loan_items || []).forEach((item) => {
            form.returns[item.id] = {
                condition_on_return: item.condition_on_loan || 'tot',
                fine_amount: Number(item.fine_amount || 0),
                notes: item.notes || '',
            };
        });
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tải được phiếu mượn.', { title: 'Lỗi' });
    } finally {
        loading.value = false;
    }
}

async function submitReturn() {
    if (!form.return_date) {
        toast.warn('Vui lòng nhập ngày trả.');
        return;
    }
    saving.value = true;
    try {
        await loansApi.returnBooks(props.loanId, {
            return_date: form.return_date,
            returns: Object.fromEntries(
                Object.entries(form.returns).map(([key, value]) => [
                    key,
                    {
                        condition_on_return: value.condition_on_return,
                        fine_amount: Number(value.fine_amount || 0),
                    },
                ])
            ),
        });
        toast.success('Trả sách thành công.', { title: 'Thành công' });
        router.visit(route('admin.loans.show', props.loanId));
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không xử lý trả sách được.', { title: 'Lỗi' });
    } finally {
        saving.value = false;
    }
}

onMounted(loadDetail);
</script>

<template>
    <Head title="Trả sách" />
    <AdminLayout
        title="Phiếu mượn"
        :breadcrumbs="[
            { label: 'Phiếu mượn', href: route('admin.loans.index') },
            { label: 'Trả sách' },
        ]"
    >
        <div v-if="loading" class="text-sm text-slate-500">Đang tải dữ liệu...</div>

        <div v-else-if="loan" class="space-y-4">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4">
                <h3 class="font-bold mb-2">Thông tin phiếu mượn</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <div><b>Mã phiếu:</b> {{ loan.loan_code || `#${loan.id}` }}</div>
                    <div><b>Ngày hẹn trả:</b> {{ loan.due_date || '-' }}</div>
                    <div><b>Độc giả:</b> {{ loan.library_card_name || '-' }}</div>
                    <div><b>Số ngày đã mượn:</b> {{ borrowedDays }} ngày</div>
                    <div><b>Ngày mượn:</b> {{ loan.loan_date || '-' }}</div>
                    <div><b>Trạng thái:</b> {{ loan.status_label || loan.status }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4">
                <h3 class="font-bold mb-2">Thông tin trả sách</h3>
                <label class="space-y-1 block max-w-xs">
                    <span class="text-sm font-medium">Ngày trả</span>
                    <input v-model="form.return_date" type="date" class="admin-filter-input w-full" />
                </label>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 overflow-x-auto">
                <div class="p-4 font-bold">Danh sách sách trả</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-4 py-2 text-left">STT</th>
                            <th class="px-4 py-2 text-left">Tên sách</th>
                            <th class="px-4 py-2 text-left">Tình trạng khi mượn</th>
                            <th class="px-4 py-2 text-left">Tình trạng khi trả</th>
                            <th class="px-4 py-2 text-left">Tiền phạt (VND)</th>
                            <th class="px-4 py-2 text-left">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, idx) in loan.loan_items || []" :key="item.id" class="border-t border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-2">{{ idx + 1 }}</td>
                            <td class="px-4 py-2">{{ item.book_title || '-' }}</td>
                            <td class="px-4 py-2">{{ item.condition_on_loan_label || item.condition_on_loan || '-' }}</td>
                            <td class="px-4 py-2">
                                <select v-model="form.returns[item.id].condition_on_return" class="admin-filter-select w-52">
                                    <option v-for="opt in conditions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input v-model.number="form.returns[item.id].fine_amount" type="number" min="0" class="admin-filter-input w-44" />
                            </td>
                            <td class="px-4 py-2">
                                <input v-model="form.returns[item.id].notes" type="text" class="admin-filter-input w-56" placeholder="Ghi chú..." />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center gap-2">
                <button class="admin-filter-btn px-4 py-2.5 min-h-[44px]" :disabled="saving" @click="submitReturn">
                    {{ saving ? 'Đang xử lý...' : 'Xác nhận trả sách' }}
                </button>
                <button class="admin-filter-btn px-4 py-2.5 min-h-[44px]" @click="router.visit(route('admin.loans.show', props.loanId))">
                    Hủy
                </button>
            </div>
        </div>
    </AdminLayout>
</template>
