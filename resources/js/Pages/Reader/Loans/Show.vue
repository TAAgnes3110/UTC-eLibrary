<script setup>
import { computed, onMounted, ref } from 'vue'
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
const renewalSubmitting = ref(false)
const renewalNote = ref('')

const renewalEligibility = computed(() => loan.value?.renewal_eligibility ?? null)
const canRequestRenewal = computed(() => renewalEligibility.value?.eligible === true)
const renewalBlockMessage = computed(() => {
    if (!loan.value || loan.value.return_date) return ''
    const e = renewalEligibility.value
    if (!e || e.eligible) return ''
    return e.message || 'Hiện không thể gửi yêu cầu gia hạn.'
})

function formatDate(v) {
    if (!v) return '—'
    const d = new Date(v)
    if (Number.isNaN(d.getTime())) return '—'
    return d.toLocaleDateString('vi-VN')
}

function renewalStatusLabel(s) {
    if (s === 'approved') return 'Đã duyệt'
    if (s === 'rejected') return 'Đã từ chối'
    if (s === 'pending') return 'Chờ xử lý'
    return s || '—'
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

async function submitRenewal() {
    if (!canRequestRenewal.value || renewalSubmitting.value) return
    renewalSubmitting.value = true
    try {
        await meLoansApi.requestRenewal(props.loanId, {
            request_note: renewalNote.value.trim() || undefined,
        })
        toast.success('Đã gửi yêu cầu gia hạn. Thủ thư sẽ xử lý trong thời gian sớm nhất.', { title: 'Gia hạn' })
        renewalNote.value = ''
        await loadDetail()
    } catch (e) {
        const msg = e?.response?.data?.messages || e?.response?.data?.message
        toast.error(msg || 'Không gửi được yêu cầu gia hạn.', { title: 'Gia hạn' })
    } finally {
        renewalSubmitting.value = false
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

                <div
                    v-if="!loan.return_date && (loan.status === 'da_muon' || loan.status === 'qua_han')"
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/70 dark:border-slate-800 p-4 space-y-3"
                >
                    <h3 class="text-base font-bold">Gia hạn phiếu mượn</h3>
                    <p v-if="renewalBlockMessage" class="text-sm text-amber-800 dark:text-amber-200/90">
                        {{ renewalBlockMessage }}
                    </p>
                    <template v-else-if="canRequestRenewal">
                        <p class="text-sm text-slate-600 dark:text-slate-400">
                            Theo chính sách thẻ của bạn: còn
                            <strong>{{ renewalEligibility?.remaining_renewals ?? 0 }}</strong>
                            lần gia hạn, mỗi lần thêm
                            <strong>{{ renewalEligibility?.extension_days ?? 0 }}</strong>
                            ngày. Hạn trả sau khi duyệt (dự kiến):
                            <strong>{{ renewalEligibility?.proposed_due_date ? formatDate(renewalEligibility.proposed_due_date) : '—' }}</strong>.
                        </p>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Ghi chú gửi thủ thư (tuỳ chọn)</label>
                        <textarea
                            v-model="renewalNote"
                            rows="3"
                            maxlength="1000"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"
                            placeholder="Ví dụ: đang trong kỳ thi, mong được gia hạn..."
                        />
                        <button
                            type="button"
                            class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50"
                            :disabled="renewalSubmitting"
                            @click="submitRenewal"
                        >
                            {{ renewalSubmitting ? 'Đang gửi...' : 'Gửi yêu cầu gia hạn' }}
                        </button>
                    </template>
                    <div v-if="(loan.renewal_requests || []).length" class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <p class="text-sm font-semibold mb-2">Lịch sử yêu cầu</p>
                        <ul class="space-y-2 text-sm">
                            <li v-for="r in loan.renewal_requests" :key="r.id" class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
                                <span class="font-medium">{{ renewalStatusLabel(r.status) }}</span>
                                — hạn đề xuất {{ formatDate(r.requested_due_date) }}
                                <span v-if="r.review_note" class="block text-xs text-slate-600 dark:text-slate-400 mt-1">{{ r.review_note }}</span>
                            </li>
                        </ul>
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
