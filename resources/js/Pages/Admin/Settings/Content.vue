<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Icon } from '@iconify/vue';
import { ref, computed } from 'vue';
import { Button } from '@/Components/ui/button';

const libraryRules = ref('');
const userGuide = ref('');

const hasCurrentRules = computed(() => (libraryRules.value || '').trim().length > 0);
const currentRulesPreview = computed(() => {
    const t = (libraryRules.value || '').trim();
    if (!t) return null;
    return t.length > 120 ? t.slice(0, 120) + '…' : t;
});

const save = () => {
    // TODO: API lưu nội quy & hướng dẫn
};
</script>

<template>
    <Head title="Nội quy & Hướng dẫn - Admin" />
    <AdminLayout
        title="Nội quy & Hướng dẫn"
        :breadcrumbs="[
            { label: 'Hệ thống' },
            { label: 'Cấu hình thư viện' },
            { label: 'Nội quy & Hướng dẫn' },
        ]"
    >
        <div class="space-y-4 animate-in fade-in-50 duration-500 max-w-4xl">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">Nội quy & Hướng dẫn</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Nội quy và hướng dẫn hiển thị cho độc giả</p>
            </div>

            <!-- Quy định hiện tại (xem nhanh) -->
            <div v-if="hasCurrentRules" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 px-4 py-3">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">Quy định hiện tại</p>
                <p class="text-sm text-slate-700 dark:text-slate-300 line-clamp-2">{{ currentRulesPreview }}</p>
            </div>

            <section class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-800">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                        <Icon icon="lucide:file-text" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-900 dark:text-white text-sm">Nội dung thư viện</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Hiển thị trên trang chủ và thông báo cho độc giả</p>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Quy định hiện tại (Nội quy thư viện)</label>
                        <textarea
                            v-model="libraryRules"
                            rows="3"
                            class="w-full p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500/20 outline-none resize-y min-h-[72px]"
                            placeholder="Nhập nội quy thư viện..."
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Hướng dẫn sử dụng</label>
                        <textarea
                            v-model="userGuide"
                            rows="3"
                            class="w-full p-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-blue-500/20 outline-none resize-y min-h-[72px]"
                            placeholder="Hướng dẫn độc giả cách tra cứu và mượn sách..."
                        />
                    </div>
                </div>
                <div class="px-4 py-3 bg-slate-50/50 dark:bg-slate-800/30 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <Button type="button" @click="save" class="btn-admin-primary h-8 px-4">
                        Lưu thay đổi
                    </Button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
