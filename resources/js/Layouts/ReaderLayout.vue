<script setup>
import AppToastContainer from '@/Components/Shared/AppToastContainer.vue'
import ReaderHeader from '@/Layouts/Reader/ReaderHeader.vue'
import ReaderFooter from '@/Layouts/Reader/ReaderFooter.vue'

defineProps({
    title: { type: String, default: '' },
})
</script>

<template>
    <div class="flex min-h-screen min-h-[100dvh] flex-col bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
        <AppToastContainer />
        <ReaderHeader />
        <main class="max-w-[1600px] w-full min-w-0 mx-auto flex-1 p-3 lg:p-5 pb-[max(1rem,env(safe-area-inset-bottom))]">
            <slot />
        </main>
        <ReaderFooter />
    </div>
</template>
