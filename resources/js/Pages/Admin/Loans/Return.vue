<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { fetchAdminApiGet, fetchAdminApiPost } from '@/utils/adminApiAuth';
import { toast } from '@/store/toast';
import { formatVnd } from '@/utils/index';
import { calculateReturnLineFine, damagePercentRequired } from '@/utils/loanReturnFine';

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

const totalFine = computed(() =>
    Object.values(form.returns).reduce((sum, row) => sum + Number(row?.fine_amount || 0), 0)
);

function daysBorrowed(loanDate, returnDate) {
    if (!loanDate || !returnDate) return 0;
    const a = new Date(loanDate);
    const b = new Date(returnDate);
    const ms = b.setHours(0, 0, 0, 0) - a.setHours(0, 0, 0, 0);
    return Math.max(0, Math.floor(ms / 86400000));
}

const borrowedDays = computed(() => daysBorrowed(loan.value?.loan_date, form.return_date));

function resolveBookPrice(item) {
    const row = form.returns[item?.id];
    const raw = row?.book_price_override ?? item?.book_price ?? item?.book?.price;
    const n = Number(raw);
    return Number.isFinite(n) && n > 0 ? n : 0;
}

function needsBookPrice(item) {
    const row = form.returns[item?.id];
    if (!row) {
        return false;
    }
    if (!['hong', 'mat'].includes(row.condition_on_return)) {
        return false;
    }
    return resolveBookPrice(item) <= 0;
}

function recalculateLineFine(itemId) {
    const item = (loan.value?.loan_items || []).find((row) => row.id === itemId);
    const row = form.returns[itemId];
    if (!item || !row) {
        return;
    }

    row.fine_amount = calculateReturnLineFine({
        dueDate: loan.value?.due_date,
        returnDate: form.return_date,
        conditionOnReturn: row.condition_on_return,
        damagePercent: row.damage_percent,
        bookPrice: resolveBookPrice(item),
        quantity: item.quantity,
        finePolicy: loan.value?.fine_policy,
    });
}

function recalculateAllFines() {
    (loan.value?.loan_items || []).forEach((item) => recalculateLineFine(item.id));
}

function onConditionChange(itemId, condition) {
    const row = form.returns[itemId];
    if (!row) {
        return;
    }
    if (condition === 'tot') {
        row.damage_percent = 0;
    } else if (condition === 'hong') {
        row.damage_percent = row.damage_percent === 0 ? '' : (row.damage_percent ?? '');
    } else if (condition === 'mat') {
        row.damage_percent = 100;
    }
    recalculateLineFine(itemId);
}

function truncateNote(text, maxLen = 28) {
    const s = String(text ?? '').trim();
    if (!s) {
        return '';
    }
    return s.length > maxLen ? `${s.slice(0, maxLen)}…` : s;
}

function buildReturnPayload() {
    return {
        return_date: form.return_date,
        returns: Object.fromEntries(
            Object.entries(form.returns).map(([key, value]) => {
                const entry = {
                    condition_on_return: value.condition_on_return,
                    fine_amount: Number(value.fine_amount || 0),
                };
                if (value.condition_on_return === 'hong') {
                    entry.damage_percent = Number(value.damage_percent);
                }
                return [key, entry];
            })
        ),
    };
}

function validateReturnForm() {
    if (!form.return_date) {
        toast.warn('Vui lòng nhập ngày trả.');
        return false;
    }

    for (const item of loan.value?.loan_items || []) {
        const row = form.returns[item.id];
        if (!row || !damagePercentRequired(row.condition_on_return)) {
            continue;
        }
        const pct = Number(row.damage_percent);
        if (!Number.isFinite(pct) || pct < 1 || pct > 100) {
            toast.warn(`Vui lòng nhập hư hỏng (1–100) cho «${item.book_title || 'sách'}».`);
            return false;
        }
        if (resolveBookPrice(item) <= 0) {
            toast.warn(`Vui lòng nhập giá bìa cho «${item.book_title || 'sách'}» để tính phạt hư hỏng.`);
            return false;
        }
    }

    for (const item of loan.value?.loan_items || []) {
        const row = form.returns[item.id];
        if (!row || row.condition_on_return !== 'mat') {
            continue;
        }
        if (resolveBookPrice(item) <= 0) {
            toast.warn(`Vui lòng nhập giá bìa cho «${item.book_title || 'sách'}» để tính phạt mất sách.`);
            return false;
        }
    }

    return true;
}

function resolveReturnErrorMessage(error) {
    if (error?.response?.status === 401) {
        return 'Phiên đăng nhập không hợp lệ. Tải lại trang (F5), đăng nhập lại rồi thử trả sách.';
    }
    return error?.response?.data?.messages || error?.message || 'Không xử lý trả sách được.';
}

async function loadDetail() {
    loading.value = true;
    try {
        const res = await fetchAdminApiGet(`/loans/${props.loanId}`);
        loan.value = res?.data ?? null;

        form.returns = {};
        (loan.value?.loan_items || []).forEach((item) => {
            const condition = item.condition_on_loan || 'tot';
            form.returns[item.id] = {
                condition_on_return: condition,
                damage_percent: condition === 'mat' ? 100 : condition === 'hong' ? '' : 0,
                book_price_override: null,
                fine_amount: 0,
                notes: '',
            };
        });
        recalculateAllFines();
    } catch (e) {
        toast.error(resolveReturnErrorMessage(e), { title: 'Lỗi' });
    } finally {
        loading.value = false;
    }
}

async function submitReturn() {
    if (saving.value) {
        return;
    }
    if (!validateReturnForm()) {
        return;
    }

    const payload = buildReturnPayload();
    saving.value = true;
    try {
        await fetchAdminApiPost(`/loans/${props.loanId}/return`, payload);
        toast.success('Trả sách thành công.', { title: 'Thành công' });
        router.visit(route('admin.loans.show', props.loanId));
    } catch (e) {
        toast.error(resolveReturnErrorMessage(e), { title: 'Lỗi' });
    } finally {
        saving.value = false;
    }
}

watch(() => form.return_date, recalculateAllFines);

watch(
    () => form.returns,
    () => recalculateAllFines(),
    { deep: true }
);

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
                    <input v-model="form.return_date" type="date" class="admin-filter-input w-full min-h-[44px]" />
                </label>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 overflow-x-auto">
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="font-bold">Danh sách sách trả</div>
                </div>
                <table class="min-w-full text-sm return-books-table">
                    <thead class="bg-slate-50 dark:bg-slate-800/60">
                        <tr>
                            <th class="px-4 py-3 text-center w-14">STT</th>
                            <th class="px-4 py-3 text-left min-w-[12rem]">Tên sách</th>
                            <th class="px-4 py-3 text-left whitespace-nowrap">Tình trạng khi mượn</th>
                            <th class="px-4 py-3 text-left whitespace-nowrap">Tình trạng khi trả</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap w-28">Hư hỏng (%)</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap w-36">Tiền phạt (đ)</th>
                            <th class="px-4 py-3 text-left w-40">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, idx) in loan.loan_items || []"
                            :key="item.id"
                            class="border-t border-slate-100 dark:border-slate-800"
                        >
                            <td class="px-4 py-3 text-center tabular-nums text-slate-600 dark:text-slate-300">
                                {{ idx + 1 }}
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800 dark:text-slate-100 leading-snug">
                                {{ item.book_title || '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ item.condition_on_loan_label || item.condition_on_loan || '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <select
                                    v-model="form.returns[item.id].condition_on_return"
                                    class="admin-filter-select w-full max-w-[13rem] min-h-[44px]"
                                    @change="onConditionChange(item.id, form.returns[item.id].condition_on_return)"
                                >
                                    <option v-for="opt in conditions" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="inline-flex flex-col items-center gap-1">
                                    <input
                                        v-if="form.returns[item.id].condition_on_return === 'hong'"
                                        v-model.number="form.returns[item.id].damage_percent"
                                        type="number"
                                        min="1"
                                        max="100"
                                        placeholder="1–100"
                                        class="admin-filter-input w-20 min-h-[44px] text-center"
                                        @input="recalculateLineFine(item.id)"
                                    />
                                    <span
                                        v-else
                                        class="inline-flex min-h-[44px] min-w-[3rem] items-center justify-center tabular-nums font-medium text-slate-700 dark:text-slate-200"
                                    >
                                        {{ form.returns[item.id].condition_on_return === 'mat' ? 100 : 0 }}
                                    </span>
                                    <input
                                        v-if="needsBookPrice(item)"
                                        v-model.number="form.returns[item.id].book_price_override"
                                        type="number"
                                        min="1"
                                        step="1000"
                                        placeholder="Giá bìa"
                                        title="Sách chưa có giá trong hệ thống — nhập giá bìa để tính phạt"
                                        class="admin-filter-input w-28 min-h-[36px] text-xs text-center"
                                        @input="recalculateLineFine(item.id)"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <input
                                    :value="formatVnd(form.returns[item.id].fine_amount)"
                                    type="text"
                                    readonly
                                    tabindex="-1"
                                    class="admin-filter-input w-full min-h-[44px] bg-slate-50 dark:bg-slate-800/80 font-semibold tabular-nums text-right"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <input
                                    v-model="form.returns[item.id].notes"
                                    type="text"
                                    class="admin-filter-input w-full min-h-[44px] truncate"
                                    :title="item.notes || form.returns[item.id].notes || ''"
                                    :placeholder="truncateNote(item.notes) || 'Ghi chú...'"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div
                    v-if="(loan.loan_items || []).length"
                    class="flex items-center justify-end gap-3 px-4 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/90 dark:bg-slate-800/50"
                >
                    <span class="text-sm font-medium text-slate-600 dark:text-slate-300 shrink-0">
                        Tổng tiền phạt
                    </span>
                    <div
                        class="inline-flex items-center justify-end gap-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 px-5 py-3 min-h-[44px] shadow-sm shrink-0"
                    >
                        <span class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">VND</span>
                        <span class="text-xl font-bold tabular-nums text-slate-900 dark:text-white">
                            {{ formatVnd(totalFine) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn-admin-green px-4 py-2.5 min-h-[44px] disabled:opacity-50 disabled:pointer-events-none"
                    :disabled="saving"
                    @click="submitReturn"
                >
                    {{ saving ? 'Đang xử lý...' : 'Xác nhận trả sách' }}
                </button>
                <button
                    type="button"
                    class="admin-filter-btn px-4 py-2.5 min-h-[44px]"
                    :disabled="saving"
                    @click="router.visit(route('admin.loans.show', props.loanId))"
                >
                    Hủy
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.return-books-table th,
.return-books-table td {
    vertical-align: middle;
}
</style>
