<script setup>
import AppToastContainer from '@/Components/Shared/AppToastContainer.vue'
import ReaderHeader from '@/Layouts/Reader/ReaderHeader.vue'
import ReaderFooter from '@/Layouts/Reader/ReaderFooter.vue'

const props = defineProps({
    title: { type: String, default: '' },
    /** Bỏ giới hạn max-width của main (vd. trang giỏ sách rộng toàn khung) */
    fullWidth: { type: Boolean, default: false },
})
</script>

<template>
    <div class="flex min-h-[100dvh] flex-col bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <AppToastContainer />
        <ReaderHeader class="shrink-0" />
        <!-- flex-1 + min-h-0 + overflow: nội dung cuộn trong main; footer không bị “dính” theo chiều cao bảng -->
        <main
            class="mx-auto flex w-full min-w-0 flex-1 min-h-0 flex-col overflow-y-auto p-3 pb-[max(1rem,env(safe-area-inset-bottom))] lg:p-5"
            :class="props.fullWidth ? 'max-w-none' : 'max-w-[1600px]'"
        >
            <slot />
        </main>
        <ReaderFooter class="shrink-0" />
    </div>
</template>
