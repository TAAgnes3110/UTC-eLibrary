<script setup>
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';

defineProps({
    show: Boolean,
    book: Object
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm"
            @click.self="$emit('close')"
        >
            <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95 duration-300 p-10 text-center border-b-8 border-rose-600">
                <div class="w-24 h-24 rounded-full bg-rose-50 dark:bg-rose-900/20 flex items-center justify-center mx-auto mb-8 animate-bounce transition-all duration-1000">
                    <Icon icon="lucide:alert-triangle" class="w-12 h-12 text-rose-600 dark:text-rose-400" />
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-4 uppercase tracking-widest">Xác nhận xóa?</h3>
                <p class="text-slate-500 dark:text-slate-400 font-bold mb-10 leading-relaxed px-4">
                    Hành động này sẽ xóa vĩnh viễn cuốn sách <span class="text-rose-600">"{{ book?.title }}"</span> khỏi cơ sở dữ liệu.
                </p>
                <div class="flex flex-col gap-3">
                    <Button @click="$emit('confirm')" class="h-14 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-black uppercase tracking-widest shadow-lg shadow-rose-600/30">
                        Xác nhận xóa
                    </Button>
                    <Button @click="$emit('close')" variant="ghost" class="h-12 rounded-2xl font-bold text-slate-400">
                        Hủy bỏ
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
