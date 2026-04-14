<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { onMounted, ref } from 'vue'
import ReaderLayout from '@/Layouts/ReaderLayout.vue'
import { profileApi } from '@/api/profile'

const rows = ref([])
const loading = ref(false)
const loadError = ref('')

const statusLabel = (status) => {
    if (status === 'approved') return 'Đã duyệt'
    if (status === 'rejected') return 'Đã từ chối'
    return 'Chờ duyệt'
}

const statusClass = (status) => {
    if (status === 'approved') return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
    if (status === 'rejected') return 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
    return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
}

const loadRequests = async () => {
    loading.value = true
    loadError.value = ''
    try {
        const response = await profileApi.myProfileUpdateRequests()
        rows.value = Array.isArray(response?.data) ? response.data : []
    } catch {
        rows.value = []
        loadError.value = 'Không thể tải lịch sử yêu cầu. Vui lòng thử lại.'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    loadRequests()
})
</script>

<template>
    <ReaderLayout>
        <Head title="Lịch sử yêu cầu cập nhật - Thư viện số UTC" />

        <div class="mx-auto max-w-6xl space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-4 dark:border-slate-700 dark:bg-slate-900">
                <div>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white">Lịch sử yêu cầu cập nhật hồ sơ</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Theo dõi trạng thái tất cả yêu cầu bạn đã gửi.</p>
                </div>
                <Link
                    :href="route('reader.profile')"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    <Icon icon="lucide:arrow-left" class="h-4 w-4" />
                    Quay lại hồ sơ
                </Link>
            </div>

            <div
                v-if="loadError"
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
            >
                {{ loadError }}
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[940px] text-left">
                        <thead class="border-b border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/70">
                            <tr>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Thời gian</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Mã yêu cầu</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Yêu cầu thay đổi</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Trạng thái</th>
                                <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500">Ghi chú duyệt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-if="loading">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Đang tải dữ liệu...</td>
                            </tr>
                            <tr v-else-if="rows.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">Bạn chưa gửi yêu cầu nào.</td>
                            </tr>
                            <tr v-for="item in rows" :key="item.id" class="align-top">
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    {{ item.created_at ? new Date(item.created_at).toLocaleString('vi-VN') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                                    #{{ item.id }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="space-y-0.5">
                                        <p>Mã: {{ item.requested_code || '—' }}</p>
                                        <p>Khoa: {{ item.requested_faculty?.name || '—' }}</p>
                                        <p>Niên khóa: {{ item.requested_period?.name || '—' }}</p>
                                        <p>Lớp: {{ item.requested_class_code || '—' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="[statusClass(item.status), 'inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold']">
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    {{ item.review_note || '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </ReaderLayout>
</template>
