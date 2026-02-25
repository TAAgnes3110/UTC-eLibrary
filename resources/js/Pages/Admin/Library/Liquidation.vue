<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const searchQuery = ref('');
const showModal = ref(false);

const props = defineProps({
    liquidations: { type: Array, default: () => [
        { id: 1, code: 'TL-2024-001', cause: 'hỏng/rách', date: '2024-01-15', qty: 25, total_value: 3500000, decision_no: 'QD-124/TV-UTC', status: 'approved' },
        { id: 2, code: 'TL-2024-002', cause: 'hết hạn (cũ)', date: '2024-02-10', qty: 120, total_value: 12800000, decision_no: 'QD-156/TV-UTC', status: 'pending' },
        { id: 3, code: 'TL-2024-003', cause: 'mất/thất thoát', date: '2024-02-25', qty: 5, total_value: 850000, decision_no: null, status: 'pending' },
    ]}
});

const filtered = computed(() => {
    return props.liquidations.filter(l =>
        l.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        l.cause.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (l.decision_no && l.decision_no.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const getStatusStyle = (status) => {
    switch(status) {
        case 'approved': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const getStatusLabel = (status) => {
    return status === 'approved' ? 'Đã duyệt' : 'Chờ duyệt';
};

const getCauseIcon = (cause) => {
    if (cause.includes('hỏng')) return 'lucide:book-x';
    if (cause.includes('mất')) return 'lucide:search-x';
    return 'lucide:calendar-x';
};
</script>

<template>
    <Head title="Thanh lý tài sản - Admin" />
    <AdminLayout
        title="Thanh lý tài sản"
        :breadcrumbs="[
            { label: 'Nghiệp vụ Kho' },
            { label: 'Thanh lý' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Action Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Danh sách thanh lý</h2>
                <div class="flex items-center gap-1.5">
                    <!-- Quyết định -->
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-text" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Quyết định</span>
                    </button>

                    <!-- Thêm mới -->
                    <button
                        @click="showModal = true"
                        class="btn-action-primary"
                    >
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Tạo phiếu thanh lý</span>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" placeholder="Tìm mã phiếu, lý do, số quyết định..." class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800/50 border-none text-sm" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-12 text-center">STT</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã phiếu</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Lý do thanh lý</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số quyết định</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Ngày lập</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Số lượng</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Giá trị TL</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(l, index) in filtered" :key="l.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4 text-center font-mono text-xs text-slate-400">{{ index + 1 }}</td>
                                <td class="p-4 font-bold text-slate-900 dark:text-white uppercase text-[13px] tracking-tight">{{ l.code }}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1.5">
                                        <Icon :icon="getCauseIcon(l.cause)" class="w-3.5 h-3.5 text-slate-400" />
                                        <span class="text-[12px] font-medium text-slate-700 dark:text-slate-300 uppercase tracking-tight">{{ l.cause }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <span v-if="l.decision_no" class="text-[11px] font-bold font-mono text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded border border-blue-100 dark:border-blue-800 shadow-sm">{{ l.decision_no }}</span>
                                    <span v-else class="text-[11px] text-slate-300 italic">Chưa có QĐ</span>
                                </td>
                                <td class="p-4 text-center text-[12px] text-slate-500 font-medium">{{ l.date }}</td>
                                <td class="p-4 text-center font-bold text-rose-500 text-[13px]">-{{ l.qty }}</td>
                                <td class="p-4 text-right font-bold text-slate-900 dark:text-white text-[13px]">
                                    {{ formatCurrency(l.total_value) }}
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusStyle(l.status)]">
                                        {{ getStatusLabel(l.status) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all">
                                            <Icon icon="lucide:eye" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-all">
                                            <Icon icon="lucide:printer" class="w-[18px] h-[18px]" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Add Modal (Standard) -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-xs" @click="showModal = false"></div>
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-lg overflow-hidden shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-blue-600">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Phiếu đề nghị thanh lý</h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Lý do thanh lý</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button class="py-2.5 px-1 rounded-md bg-blue-50 text-blue-600 font-bold text-[11px] border-2 border-blue-200 uppercase">Hỏng / Rách</button>
                                <button class="py-2.5 px-1 rounded-md bg-slate-50 text-slate-400 font-bold text-[11px] border-2 border-slate-100 uppercase text-nowrap">Mất mát / Thất lạc</button>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Số quyết định</label>
                            <Input placeholder="Nhập số quyết định nếu có..." class="h-9 rounded-md border-slate-200 text-xs shadow-sm" />
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/10 border border-red-100 rounded-lg flex gap-3 mt-4">
                            <Icon icon="lucide:alert-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
                            <p class="text-[11px] text-red-700 dark:text-red-400 font-medium leading-relaxed">Tài liệu sau khi thanh lý sẽ được chuyển trạng thái "Đã thanh lý" và không thể phục hồi trạng thái cho mượn.</p>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showModal = false" class="h-8 px-4 font-bold text-xs rounded-md">Bỏ qua</Button>
                        <Button size="sm" @click="showModal = false" class="h-8 px-6 font-bold text-xs rounded-md bg-blue-600 hover:bg-blue-700 text-white">Xác nhận</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
