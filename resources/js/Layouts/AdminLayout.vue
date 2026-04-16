<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import apiClient from '@/api/axios';
import { toast } from '@/store/toast';
import {
    buildStaffWorkQueueToastMessage,
    STAFF_WORK_QUEUE_HINT_KEY,
} from '@/utils/staffWorkQueueHint';
import AdminSidebar from '@/Layouts/Admin/AdminSidebar.vue';
import AdminHeader from '@/Layouts/Admin/AdminHeader.vue';
import AdminBreadcrumb from '@/Layouts/Admin/AdminBreadcrumb.vue';
import AppToastContainer from '@/Components/Shared/AppToastContainer.vue';

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

onMounted(async () => {
    if (!page.props.auth?.is_staff) {
        return;
    }
    try {
        if (typeof sessionStorage !== 'undefined' && sessionStorage.getItem(STAFF_WORK_QUEUE_HINT_KEY)) {
            return;
        }
        const res = await apiClient.get('/auth/user');
        const q = res?.data?.staff_work_queue;
        try {
            sessionStorage.setItem(STAFF_WORK_QUEUE_HINT_KEY, '1');
        } catch {
            //
        }
        const msg = buildStaffWorkQueueToastMessage(q);
        if (msg) {
            toast.info(msg, { title: 'Việc cần xử lý' });
        }
    } catch {
        //
    }
});

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-slate-950 transition-colors duration-300">
        <AppToastContainer />
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
