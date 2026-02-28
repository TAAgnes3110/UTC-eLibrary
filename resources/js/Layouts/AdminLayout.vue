<script setup>
import { ref, computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AdminSidebar from '@/Layouts/Admin/AdminSidebar.vue';
import AdminHeader from '@/Layouts/Admin/AdminHeader.vue';
import AdminBreadcrumb from '@/Layouts/Admin/AdminBreadcrumb.vue';

const props = defineProps({
    title: { type: String, default: 'Dashboard' },
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const sidebarOpen = ref(typeof window !== 'undefined' && window.innerWidth >= 1024);
const collapsed = computed(() => !sidebarOpen.value);

router.on('navigate', () => {
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }
});

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-slate-950 transition-colors duration-300">
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 lg:hidden transition-opacity duration-300"
            @click="sidebarOpen = false"
        />

        <AdminSidebar :sidebar-open="sidebarOpen" :collapsed="collapsed" />

        <div :class="[sidebarOpen ? 'lg:ml-60' : 'lg:ml-[56px]']" class="transition-all duration-300 min-h-screen flex flex-col min-w-0">
            <AdminHeader :title="title" :sidebar-open="sidebarOpen" :user="user" @toggle-sidebar="toggleSidebar" />
            <AdminBreadcrumb :breadcrumbs="breadcrumbs" />
            <main class="p-3 lg:p-5 max-w-[1600px] w-full min-w-0 mx-auto flex-1 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <slot />
            </main>
        </div>
    </div>
</template>
