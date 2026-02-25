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
    inventory_periods: { type: Array, default: () => [
        { id: 1, code: 'KK-2023-Q4', title: 'Kiểm kê cuối năm 2023', method: 'Toàn bộ', person: 'Nguyễn Văn A', start_date: '2023-12-25', end_date: '2023-12-30', result: 'balanced', status: 'completed' },
        { id: 2, code: 'KK-2024-Q1', title: 'Kiểm kê định kỳ Quý 1/2024', method: 'Chọn mẫu', person: 'Trần Thị B', start_date: '2024-03-20', end_date: null, result: null, status: 'processing' },
    ]}
});

const filtered = computed(() => {
    return props.inventory_periods.filter(p =>
        p.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        p.code.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const getStatusStyle = (status) => {
    switch(status) {
        case 'completed': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
        case 'processing': return 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const getStatusLabel = (status) => {
    return status === 'completed' ? 'Hoàn tất' : 'Đang thực hiện';
};

const getResultLabel = (result) => {
    if (!result) return '---';
    return result === 'balanced' ? 'Khớp dữ liệu' : 'Chênh lệch';
};
</script>

<template>
    <Head title="Kiểm kê tài sản - Admin" />
    <AdminLayout
        title="Kiểm kê tài sản"
        :breadcrumbs="[
            { label: 'Nghiệp vụ Kho' },
            { label: 'Kiểm kê' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Action Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Đợt kiểm kê</h2>
                <div class="flex items-center gap-1.5">
                    <!-- Lịch sử -->
                    <button class="btn-excel-export">
                        <Icon icon="lucide:history" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Lịch sử</span>
                    </button>

                    <!-- Thêm mới -->
                    <button
                        @click="showModal = true"
                        class="btn-action-primary"
                    >
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Bắt đầu kiểm kê</span>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" placeholder="Tìm tên đợt, mã kiểm kê..." class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800/50 border-none text-sm" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Mã đợt</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Tên đợt kiểm kê</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Hình thức</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Người phụ trách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Thời gian</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Kết quả</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in filtered" :key="p.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4">
                                    <span class="text-[12px] font-bold font-mono text-slate-500">{{ p.code }}</span>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900 dark:text-white text-[13px] tracking-tight">{{ p.title }}</div>
                                </td>
                                <td class="p-4">
                                    <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded text-[11px] font-bold uppercase">
                                        {{ p.method }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="text-[13px] font-medium text-slate-600 dark:text-slate-300">{{ p.person }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="text-[11px] text-slate-500 font-medium">
                                        {{ p.start_date }} <Icon icon="lucide:arrow-right" class="inline w-3 h-3 mx-1" /> {{ p.end_date || '...' }}
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="['text-[12px] font-bold italic', p.result === 'balanced' ? 'text-emerald-600' : 'text-slate-400']">
                                        {{ getResultLabel(p.result) }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusStyle(p.status)]">
                                        {{ getStatusLabel(p.status) }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-end gap-1">
                                        <button class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-all">
                                            <Icon icon="lucide:eye" class="w-[18px] h-[18px]" />
                                        </button>
                                        <button class="p-1.5 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded transition-all">
                                            <Icon icon="lucide:more-vertical" class="w-[18px] h-[18px]" />
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
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Thiết lập Đợt kiểm kê</h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tên đợt kiểm kê</label>
                            <Input placeholder="Ví dụ: Kiểm kê định kỳ năm 2024" class="h-9 rounded-md border-slate-200 text-xs" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Người phụ trách</label>
                                <Input value="Admin Root" disabled class="h-9 rounded-md bg-slate-50 border-slate-200 text-xs font-medium" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hình thức</label>
                                <select class="w-full h-9 px-3 rounded-md border border-slate-200 text-xs outline-none bg-white">
                                    <option>Toàn bộ kho</option>
                                    <option>Theo danh mục</option>
                                </select>
                            </div>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/10 border border-blue-100 rounded-lg flex gap-3 mt-4">
                            <Icon icon="lucide:info" class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" />
                            <p class="text-[11px] text-blue-700 dark:text-blue-400 font-medium leading-relaxed">Khi bắt đầu kiểm kê, hệ thống sẽ tự động chốt số liệu tồn kho hiện tại để đối soát thực tế.</p>
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
