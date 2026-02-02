<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';

const stats = [
  {
    title: 'Tổng số sách',
    value: '12,345',
    icon: 'lucide:book',
    change: '+12%',
    desc: 'so với tháng trước',
    color: 'text-blue-600',
    bg: 'bg-blue-100',
  },
  {
    title: 'Thành viên mới',
    value: '543',
    icon: 'lucide:users',
    change: '+5%',
    desc: 'trong tuần này',
    color: 'text-green-600',
    bg: 'bg-green-100',
  },
  {
    title: 'Lượt mượn',
    value: '2,109',
    icon: 'lucide:book-open-check',
    change: '-2%',
    desc: 'so với hôm qua',
    color: 'text-orange-600',
    bg: 'bg-orange-100',
  },
   {
    title: 'Đang trễ hạn',
    value: '12',
    icon: 'lucide:alert-circle',
    change: '+1',
    desc: 'cần xử lý gấp',
    color: 'text-red-600',
    bg: 'bg-red-100',
  },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="space-y-6 animate-in fade-in-50 duration-500">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight text-slate-800 dark:text-slate-100">Tổng quan</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Chào mừng quay trở lại hệ thống quản lý thư viện.</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card v-for="(stat, index) in stats" :key="index" class="border-none shadow-sm hover:shadow-md transition-all dark:bg-slate-900 border border-transparent dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">
                            {{ stat.title }}
                        </CardTitle>
                        <div :class="`p-2 rounded-lg ${stat.bg} dark:bg-slate-800 transition-colors`">
                             <Icon :icon="stat.icon" :class="`h-4 w-4 ${stat.color}`" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-800 dark:text-white transition-colors">{{ stat.value }}</div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            <span :class="stat.change.includes('+') ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" class="font-medium">
                                {{ stat.change }}
                            </span>
                            {{ stat.desc }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Activity / Placeholder -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                <Card class="col-span-4 border-none shadow-sm dark:bg-slate-900 transition-colors">
                    <CardHeader>
                        <CardTitle class="dark:text-white">Biểu đồ mượn trả</CardTitle>
                    </CardHeader>
                    <CardContent class="pl-2">
                       <div class="h-[300px] flex items-center justify-center text-slate-400 bg-slate-50 dark:bg-slate-800/50 rounded-md border border-dashed border-slate-200 dark:border-slate-700">
                           <span class="flex items-center gap-2">
                               <Icon icon="lucide:bar-chart-3" class="h-5 w-5" />
                               Biểu đồ sẽ hiển thị ở đây
                           </span>
                       </div>
                    </CardContent>
                </Card>
                <Card class="col-span-3 border-none shadow-sm dark:bg-slate-900 transition-colors">
                    <CardHeader>
                        <CardTitle class="dark:text-white">Hoạt động gần đây</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-8">
                            <!-- Dummy List Items -->
                            <div class="flex items-center" v-for="i in 5" :key="i">
                                <div class="space-y-1">
                                    <p class="text-sm font-medium leading-none dark:text-white">Sinh viên Nguyễn Văn A</p>
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Đã mượn cuốn "Lập trình PHP nâng cao"</p>
                                </div>
                                <div class="ml-auto font-medium text-xs text-slate-500 dark:text-slate-500">0{{i}} phút trước</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
