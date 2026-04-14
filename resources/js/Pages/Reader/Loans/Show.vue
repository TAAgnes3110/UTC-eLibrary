<script setup>
import { onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { meLoansApi } from '@/api/meLoans'
import { toast } from '@/store/toast'

const props = defineProps({
    loanId: { type: Number, required: true },
})

const loading = ref(false)
const loan = ref(null)

function formatDate(v) {
    if (!v) return '—'
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('vi-VN')
}

async function loadDetail() {
    loading.value = true
    try {
        const res = await meLoansApi.get(props.loanId)
        loan.value = res?.data ?? null
    } catch (e) {
        toast.error(e?.response?.data?.messages || 'Không tải được chi tiết phiếu.', { title: 'Phiếu mượn' })
    } finally {
        loading.value = false
    }
}

onMounted(loadDetail)
</script>

<template>
    <ReaderLayout>
        <Head title="Chi tiết phiếu mượn" />
        <div class="mx-auto max-w-5xl space-y-4 animate-in fade-in-50 duration-500">
            <div>
                <Link
                    :href="route('reader.services.loan-requests')"
                    class="inline-flex min-h-[44px] items-center gap-2 text-sm font-semibold text-blue-800 hover:underline dark:text-blue-400"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                    Quay lại quản lý phiếu
                </Link>
            </div>

            <div v-if="loading" class="text-sm text-slate-500 dark:text-slate-400">Đang tải dữ liệu...</div>

            <template v-else-if="loan">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4">
                    <h2 class="text-base font-bold mb-3">Thông tin chính</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                        <div><b>Mã phiếu:</b> {{ loan.loan_code || `#${loan.id}` }}</div>
                        <div><b>Mã thẻ:</b> {{ loan.library_card_number || '—' }}</div>
                        <div><b>Tên độc giả:</b> {{ loan.library_card_name || '—' }}</div>
                        <div><b>Người tạo:</b> {{ loan.created_by_name || '—' }}</div>
                        <div><b>Ngày mượn:</b> {{ formatDate(loan.loan_date) }}</div>
                        <div><b>Ngày hẹn trả:</b> {{ formatDate(loan.due_date) }}</div>
                        <div><b>Ngày trả:</b> {{ formatDate(loan.return_date) }}</div>
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
                                <td class="px-4 py-2">{{ item.book_title || '—' }}</td>
                                <td class="px-4 py-2">{{ item.quantity }}</td>
                                <td class="px-4 py-2">{{ item.condition_on_loan_label || item.condition_on_loan || '—' }}</td>
                                <td class="px-4 py-2">{{ item.condition_on_return || '—' }}</td>
                                <td class="px-4 py-2">{{ item.fine_amount ?? 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('reader.services.loan-requests')"
                        class="admin-filter-btn px-3 py-2"
                    >
                        Quay lại
                    </Link>
                </div>
            </template>
        </div>

    </ReaderLayout>
</template>
