<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const activeTab = ref('import');
const searchQuery = ref('');
const showModal = ref(false);

const props = defineProps({
    slips: { type: Array, default: () => [
        { id: 1, code: 'PN-2024-001', type: 'import', source: 'NXB Giáo Dục', date: '2024-02-10', item_count: 5, total_qty: 150, total_value: 12500000, status: 'completed' },
        { id: 2, code: 'PN-2024-002', type: 'import', source: 'Fahasa', date: '2024-02-15', item_count: 3, total_qty: 30, total_value: 2100000, status: 'completed' },
        { id: 3, code: 'PX-2024-001', type: 'export', target: 'Kho Khoa CNTT', date: '2024-02-20', item_count: 2, total_qty: 20, total_value: 1800000, status: 'completed' },
        { id: 4, code: 'PN-2024-003', type: 'import', source: 'Tiền Phong', date: '2024-02-22', item_count: 4, total_qty: 40, total_value: 3200000, status: 'pending' },
    ]}
});

const filtered = computed(() => {
    return props.slips.filter(s =>
        s.type === activeTab.value &&
        (s.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
         (s.source || s.target || '').toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const form = useForm({
    id: null,
    code: '',
    type: 'import',
    source_target: '',
    date: new Date().toISOString().split('T')[0],
    notes: '',
});

const openAddModal = () => {
    form.reset();
    form.type = activeTab.value;
    showModal.value = true;
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(val);
};

const getStatusStyle = (status) => {
    switch(status) {
        case 'completed': return 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800';
        case 'pending': return 'bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800';
        default: return 'bg-slate-50 text-slate-700 border-slate-100';
    }
};

const getStatusLabel = (status) => {
    return status === 'completed' ? 'Hoàn tất' : 'Đang chờ';
};
</script>

<template>
    <Head title="Quản lý Nhập/Xuất - Admin" />
    <AdminLayout
        title="Nhập / Xuất sách"
        :breadcrumbs="[
            { label: 'Nghiệp vụ Kho' },
            { label: 'Nhập / Xuất sách' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <!-- Action Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <button
                        @click="activeTab = 'import'"
                        :class="[
                            'px-4 py-1.5 rounded-md text-[13px] font-bold transition-all',
                            activeTab === 'import' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        Phiếu nhập
                    </button>
                    <button
                        @click="activeTab = 'export'"
                        :class="[
                            'px-4 py-1.5 rounded-md text-[13px] font-bold transition-all',
                            activeTab === 'export' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        Phiếu xuất
                    </button>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Xuất excel -->
                    <button class="btn-excel-export">
                        <Icon icon="lucide:file-down" class="w-[17px] h-[17px]" />
                        <span class="tracking-tight">Xuất excel</span>
                    </button>

                    <!-- Thêm mới -->
                    <button
                        @click="openAddModal"
                        class="btn-action-primary"
                    >
                        <Icon icon="lucide:plus" class="w-[18px] h-[18px]" />
                        <span>Tạo phiếu mới</span>
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="bg-white dark:bg-slate-900 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="relative flex-1">
                    <Icon icon="lucide:search" class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                    <Input v-model="searchQuery" :placeholder="'Tìm kiếm số phiếu, ' + (activeTab === 'import' ? 'nguồn cung cấp' : 'nơi nhận') + '...'" class="pl-10 h-10 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm focus:ring-1 focus:ring-blue-500/30" />
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-sm">
                <div class="overflow-x-auto text-nowrap">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 w-12 text-center">STT</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">Số phiếu</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ activeTab === 'import' ? 'Nguồn nhập' : 'Nơi nhận' }}</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Ngày lập</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Đầu sách</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Số lượng</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Giá trị</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center">Trạng thái</th>
                                <th class="p-4 text-[11px] font-bold uppercase tracking-wider text-slate-400 text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="(s, index) in filtered" :key="s.id" class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition-all">
                                <td class="p-4 text-center font-mono text-xs text-slate-400">{{ index + 1 }}</td>
                                <td class="p-4 font-bold text-slate-900 dark:text-white uppercase text-[13px] tracking-tight">{{ s.code }}</td>
                                <td class="p-4">
                                    <span class="text-[13px] font-medium text-slate-600 dark:text-slate-300">{{ s.source || s.target }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="text-[12px] text-slate-500 dark:text-slate-400">{{ s.date }}</div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-[12px] font-bold">{{ s.item_count }}</span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded text-[11px] font-bold">
                                        {{ s.total_qty }}
                                    </span>
                                </td>
                                <td class="p-4 text-right font-bold text-slate-900 dark:text-white text-[13px]">
                                    {{ formatCurrency(s.total_value) }}
                                </td>
                                <td class="p-4 text-center">
                                    <span :class="['px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border', getStatusStyle(s.status)]">
                                        {{ getStatusLabel(s.status) }}
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
                <div class="relative bg-white dark:bg-slate-900 rounded-xl w-full max-w-xl overflow-hidden shadow-xl border border-slate-200 dark:border-slate-800">
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-blue-600">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Tạo phiếu {{ form.type === 'import' ? 'nhập' : 'xuất' }}</h3>
                        <button @click="showModal = false" class="text-white/80 hover:text-white">
                            <Icon icon="lucide:x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 grid grid-cols-2 gap-4">
                        <div class="col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Số phiếu</label>
                            <Input value="TỰ ĐỘNG TẠO" disabled class="h-9 rounded-md bg-slate-100 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 text-xs font-mono text-slate-500 dark:text-slate-400" />
                        </div>
                        <div class="col-span-1 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ngày lập</label>
                            <Input v-model="form.date" type="date" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-2 space-y-1.5">
                            <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ form.type === 'import' ? 'Nguồn gốc' : 'Điểm đến' }}</label>
                            <Input v-model="form.source_target" class="h-9 rounded-md border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-xs text-slate-900 dark:text-white" />
                        </div>
                        <div class="col-span-2 space-y-1.5 text-center p-6 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-lg bg-slate-50/50 dark:bg-slate-800/50">
                            <Icon icon="lucide:scan-barcode" class="w-8 h-8 text-blue-500 mx-auto mb-2" />
                            <p class="text-xs font-medium text-slate-600 dark:text-slate-400">Quét mã vạch hoặc thêm sách vào phiếu</p>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <Button variant="outline" size="sm" @click="showModal = false" class="h-8 px-4 font-bold text-xs rounded-md">Bỏ qua</Button>
                        <Button size="sm" @click="showModal = false" class="h-8 px-6 font-bold text-xs rounded-md bg-blue-600 hover:bg-blue-700 text-white">Lưu phiếu</Button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AdminLayout>
</template>
