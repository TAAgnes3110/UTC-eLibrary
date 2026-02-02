<script setup>
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

defineProps({
    modelValue: String,
    categories: {
        type: Array,
        default: () => []
    }
});

defineEmits(['update:modelValue', 'filter-category']);
</script>

<template>
    <div class="bg-white dark:bg-slate-900 p-6 rounded-[2rem] shadow-sm border border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4">
        <div class="relative flex-1 group">
            <Icon icon="lucide:search" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors w-5 h-5" />
            <Input
                :model-value="modelValue"
                @update:model-value="$emit('update:modelValue', $event)"
                type="text"
                placeholder="Tìm kiếm sách, tác giả, danh mục..."
                class="pl-12 h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none dark:text-white focus:ring-2 focus:ring-blue-500/20 font-medium"
            />
        </div>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" class="h-14 rounded-2xl px-6 border-slate-200 dark:border-slate-700 dark:text-slate-300">
                    <Icon icon="lucide:filter" class="w-4 h-4 mr-2" />
                    Lọc theo danh mục
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="dark:bg-slate-900 dark:border-slate-800">
                <DropdownMenuItem class="dark:text-slate-300" @click="$emit('filter-category', null)">Tất cả</DropdownMenuItem>
                <DropdownMenuItem
                    v-for="category in categories"
                    :key="category"
                    class="dark:text-slate-300"
                    @click="$emit('filter-category', category)"
                >
                    {{ category }}
                </DropdownMenuItem>
                <!-- Fallback static items if categories is empty/demo -->
                <template v-if="categories.length === 0">
                    <DropdownMenuItem class="dark:text-slate-300">Công nghệ thông tin</DropdownMenuItem>
                    <DropdownMenuItem class="dark:text-slate-300">Khoa học cơ bản</DropdownMenuItem>
                    <DropdownMenuItem class="dark:text-slate-300">Kinh tế</DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
