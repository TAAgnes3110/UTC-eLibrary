<script setup>
import { Icon } from '@iconify/vue';

defineProps({
    books: {
        type: Array,
        required: true,
        default: () => []
    }
});

defineEmits(['edit', 'delete']);

const getStockStatus = (remaining, total) => {
    const ratio = remaining / total;
    if (remaining === 0) return { label: 'Hết', class: 'bg-rose-100 text-rose-700' };
    if (ratio < 0.2) return { label: 'Sắp hết', class: 'bg-amber-100 text-amber-700' };
    return { label: 'Còn hàng', class: 'bg-emerald-100 text-emerald-700' };
};
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">ID</th>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Sách</th>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Danh Mục</th>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Tác Giả / NXB</th>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Số Lượng</th>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Giá</th>
                        <th class="p-6 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">Thao Tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <tr
                        v-for="book in books"
                        :key="book.id"
                        class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
                    >
                        <td class="p-6 font-mono text-xs font-bold text-slate-400">#{{ book.id }}</td>
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-16 bg-slate-100 dark:bg-slate-800 rounded-lg flex items-center justify-center shrink-0 border dark:border-slate-700 shadow-sm overflow-hidden group">
                                    <Icon icon="lucide:book" class="w-6 h-6 text-slate-400 group-hover:text-blue-500 transition-colors" />
                                </div>
                                <div class="space-y-0.5">
                                    <p class="font-black text-slate-900 dark:text-white leading-tight uppercase tracking-tight">{{ book.title }}</p>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ISBN: {{ book.isbn || 'N/A' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-black uppercase tracking-wider">
                                {{ book.category }}
                            </span>
                        </td>
                        <td class="p-6">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ book.author }}</p>
                            <p class="text-xs font-medium text-slate-400 uppercase tracking-tighter">{{ book.publisher }}</p>
                        </td>
                        <td class="p-6">
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black text-slate-900 dark:text-white">{{ book.quantity_remaining }} / {{ book.quantity_total }}</span>
                                    <span
                                        :class="[getStockStatus(book.quantity_remaining, book.quantity_total).class, 'px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-tighter']"
                                    >
                                        {{ getStockStatus(book.quantity_remaining, book.quantity_total).label }}
                                    </span>
                                </div>
                                <div class="w-24 h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-blue-500 rounded-full transition-all duration-500"
                                        :style="{ width: `${(book.quantity_remaining / book.quantity_total) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-6">
                            <p class="text-sm font-black text-emerald-600 dark:text-emerald-400 tracking-tighter">{{ book.price }} VNĐ</p>
                        </td>
                        <td class="p-6">
                            <div class="flex justify-end gap-1">
                                <button
                                    @click="$emit('edit', book)"
                                    class="p-2.5 text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-all hover:scale-110 active:scale-95"
                                    title="Chỉnh sửa"
                                >
                                    <Icon icon="lucide:edit-3" class="w-5 h-5" />
                                </button>
                                <button
                                    @click="$emit('delete', book)"
                                    class="p-2.5 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all hover:scale-110 active:scale-95"
                                    title="Xóa"
                                >
                                    <Icon icon="lucide:trash-2" class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div v-if="books.length === 0" class="p-20 text-center">
            <div class="w-24 h-24 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                <Icon icon="lucide:book-x" class="w-12 h-12 text-slate-300 dark:text-slate-600" />
            </div>
            <h3 class="text-xl font-black text-slate-800 dark:text-white mb-2 uppercase tracking-widest">Không tìm thấy sách</h3>
            <p class="text-slate-500 dark:text-slate-400 font-medium">Thử tìm kiếm với từ khóa khác hoặc thêm sách mới vào thư viện.</p>
        </div>
    </div>
</template>
