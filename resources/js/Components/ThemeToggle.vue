<script setup>
import { ref, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';

const isDark = ref(false);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

onMounted(() => {
    isDark.value = localStorage.getItem('theme') === 'dark' ||
                 (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);

    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <Button
        variant="ghost"
        size="icon"
        @click="toggleTheme"
        class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 rounded-xl"
    >
        <transition name="fade" mode="out-in">
            <Icon v-if="isDark" icon="lucide:moon" class="h-5 w-5" key="moon" />
            <Icon v-else icon="lucide:sun" class="h-5 w-5" key="sun" />
        </transition>
    </Button>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
