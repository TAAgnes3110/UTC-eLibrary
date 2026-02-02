<script setup>
import { Button } from '@/Components/ui/button';
import { Icon } from '@iconify/vue';
</script>

<template>
    <div class="flex justify-center pt-4">
        <div class="flex gap-1.5 p-1.5 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border dark:border-slate-800">
            <Button variant="ghost" size="icon" class="rounded-xl w-10 h-10" disabled>
                <Icon icon="lucide:chevron-left" class="w-5 h-5" />
            </Button>
            <Button size="icon" class="rounded-xl w-10 h-10 bg-blue-600 text-white font-black">1</Button>
            <Button variant="ghost" size="icon" class="rounded-xl w-10 h-10 dark:text-slate-400">2</Button>
            <Button variant="ghost" size="icon" class="rounded-xl w-10 h-10 dark:text-slate-400">3</Button>
            <Button variant="ghost" size="icon" class="rounded-xl w-10 h-10">
                <Icon icon="lucide:chevron-right" class="w-5 h-5" />
            </Button>
        </div>
    </div>
</template>
