<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Button } from '@/Components/ui/button';
import LoanPolicyAccordion from '@/Components/Admin/LibrarySettings/LoanPolicyAccordion.vue';
import { useLibrarySettingsPricingPage } from '@/composables/admin/useLibrarySettingsPricingPage';

const { form, loading, saving, loadError, errors, fetchSettings, save, cancel } = useLibrarySettingsPricingPage();

const openDigital = ref(true);

function toggleDigital() {
    openDigital.value = !openDigital.value;
}
</script>

<template>
    <Head title="Phí & tài liệu số - Admin" />
    <AdminLayout
        title="Phí & tài liệu số"
        :breadcrumbs="[{ label: 'Trang chủ' }, { label: 'Chính sách mượn', href: route('admin.library-settings.index') }, { label: 'Phí & tài liệu số' }]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500 pb-28 sm:pb-8">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-800 dark:text-white leading-8">Giá tài liệu số</h2>
                </div>
                <Button variant="outline" size="sm" class="min-h-11 gap-1.5 shrink-0 self-start" @click="fetchSettings">
                    <Icon icon="lucide:refresh-cw" class="w-4 h-4" />
                    Tải lại
                </Button>
            </div>

            <div v-if="loading" class="text-sm text-slate-500 py-8 text-center rounded-lg border border-dashed border-slate-200 dark:border-slate-700">
                Đang tải…
            </div>

            <template v-else>
                <p
                    v-if="loadError"
                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200"
                    role="alert"
                >
                    {{ loadError }}
                </p>

                <div class="space-y-3">
                    <LoanPolicyAccordion title="Tài liệu số (đồ án, luận văn)" :open="openDigital" @toggle="toggleDigital">
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Giá tải PDF sau thanh toán. Xem trước cố định 5 trang đầu (hệ thống tự xử lý).
                        </p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <label class="text-sm font-semibold text-slate-700 dark:text-slate-300">Giá tải PDF toàn bộ (VND)</label>
                                <input
                                    v-model.number="form.digital_default_pdf_download_price_vnd"
                                    type="number"
                                    min="0"
                                    inputmode="numeric"
                                    class="h-11 w-full min-h-[44px] rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-slate-700 dark:bg-slate-950/30 dark:text-slate-100"
                                >
                                <p v-if="errors.digital_default_pdf_download_price_vnd" class="text-xs font-semibold text-rose-600 dark:text-rose-300">
                                    {{ errors.digital_default_pdf_download_price_vnd }}
                                </p>
                            </div>
                        </div>
                    </LoanPolicyAccordion>
                </div>
            </template>

            <div
                class="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur-sm px-4 py-3 sm:static sm:border-0 sm:bg-transparent sm:backdrop-blur-none sm:px-0 sm:py-0 sm:mt-8 flex flex-wrap gap-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]"
            >
                <Button variant="outline" class="min-h-11 px-6" :disabled="loading || saving" @click="cancel">Hủy bỏ</Button>
                <Button class="min-h-11 px-6 bg-emerald-700 hover:bg-emerald-800 text-white" :disabled="loading || saving" @click="save">
                    {{ saving ? 'Đang lưu…' : 'Lưu' }}
                </Button>
            </div>
        </div>
    </AdminLayout>
</template>
