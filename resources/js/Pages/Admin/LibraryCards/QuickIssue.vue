<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import LibraryCardWorkflowModal from '@/Components/Admin/LibraryCards/LibraryCardWorkflowModal.vue';
import { useLibraryCardsAdminPage } from '@/composables/admin/useLibraryCardsAdminPage';
import { ref } from 'vue';

const props = defineProps({
    pageTitle: { type: String, default: 'Cấp thẻ thư viện nhanh' },
    periods: { type: Array, default: () => [] },
});

const { selectedCard, saveLoading, fieldErrors, saveCard } = useLibraryCardsAdminPage({ skipInitialLoad: true });

const showForm = ref(true);

const onSave = (payload) => saveCard(payload, { afterCreate: 'redirect' });

const adminPageTitle = 'Quản lý thẻ thư viện';
</script>

<template>
    <Head :title="`${props.pageTitle} - Admin`" />
    <AdminLayout
        :title="adminPageTitle"
        :breadcrumbs="[
            { label: 'Trang chủ' },
            { label: 'Quản lý thẻ thư viện' },
            { label: props.pageTitle },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">{{ props.pageTitle }}</h2>
            </div>

            <LibraryCardWorkflowModal
                :show="showForm"
                embedded
                :card="selectedCard"
                operation="quick"
                :loading="saveLoading"
                :field-errors="fieldErrors"
                :periods="props.periods"
                @save="onSave"
            />
        </div>
    </AdminLayout>
</template>
