<script setup>
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Icon } from '@iconify/vue';

defineProps({
    show: Boolean,
    form: Object,
    isEditing: Boolean
});

defineEmits(['close', 'submit']);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        >
            <div @click="$emit('close')" class="absolute inset-0 bg-slate-950/40 backdrop-blur-sm"></div>

            <div class="relative bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[95vh] animate-in zoom-in-95 duration-300 border-t-8 border-blue-600">
                <!-- Header -->
                <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">
                            {{ isEditing ? 'Cập nhật' : 'Thêm Sách Mới' }}
                        </h3>
                        <p class="text-sm text-slate-500 font-bold">Điền đầy đủ các thông tin cần thiết vào form bên dưới</p>
                    </div>
                    <button @click="$emit('close')" class="w-12 h-12 bg-slate-50 dark:bg-slate-800 hover:bg-rose-50 dark:hover:bg-rose-900/30 rounded-full flex items-center justify-center transition-colors group">
                        <Icon icon="lucide:x" class="w-6 h-6 text-slate-400 group-hover:text-rose-500 transition-colors" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-8 overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-12 gap-10">
                        <!-- Cover Upload -->
                        <div class="col-span-12 lg:col-span-4">
                            <div class="aspect-[2/3] bg-slate-50 dark:bg-slate-800/50 rounded-[2.5rem] border-4 border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center p-6 text-center hover:bg-blue-50/50 dark:hover:bg-blue-900/10 hover:border-blue-300 dark:hover:border-blue-500 transition-all cursor-pointer group relative overflow-hidden">
                                <input type="file" @change="form.image = $event.target.files[0]" class="absolute inset-0 opacity-0 cursor-pointer z-10" accept="image/*">
                                <div class="w-20 h-20 bg-white dark:bg-slate-800 rounded-3xl shadow-xl flex items-center justify-center mb-6 mx-auto group-hover:scale-110 group-hover:rotate-6 transition-all">
                                    <Icon icon="lucide:cloud-upload" class="w-10 h-10 text-blue-600" />
                                </div>
                                <p class="text-lg font-black text-slate-700 dark:text-slate-300">Tải ảnh bìa sách</p>
                                <p class="text-xs text-slate-400 mt-2 font-bold px-4 uppercase tracking-widest">Kéo thả file vào đây hoặc click để duyệt file</p>
                            </div>
                        </div>

                        <!-- Fields -->
                        <div class="col-span-12 lg:col-span-8 space-y-8 pb-10">
                            <div class="space-y-6">
                                <h4 class="text-xs font-black text-blue-600 uppercase tracking-[0.2em]">Thông tin cơ bản</h4>
                                <div>
                                    <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Tên sách</label>
                                    <Input
                                        v-model="form.title"
                                        placeholder="Tiêu đề đầy đủ của cuốn sách..."
                                        class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold text-slate-900 dark:text-white"
                                    />
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Danh mục</label>
                                        <select v-model="form.category_id" class="w-full h-14 px-6 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 font-bold text-slate-900 dark:text-white appearance-none">
                                            <option value="">Chọn danh mục</option>
                                            <option>Giáo trình</option>
                                            <option>Công nghệ thông tin</option>
                                            <option>Kinh tế</option>
                                            <option>Khoa học cơ bản</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Tác giả</label>
                                        <Input
                                            v-model="form.author"
                                            placeholder="Tên tác giả..."
                                            class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <h4 class="text-xs font-black text-amber-600 uppercase tracking-[0.2em]">Xuất bản & Kho</h4>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Nhà xuất bản</label>
                                        <Input v-model="form.publisher" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Năm XB</label>
                                            <Input v-model="form.year" type="number" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white text-center" />
                                        </div>
                                        <div>
                                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Số trang</label>
                                            <Input v-model="form.pages" type="number" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white text-center" />
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Giá bìa (VNĐ)</label>
                                        <Input v-model="form.price" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Số lượng nhập</label>
                                        <Input v-model="form.quantity" type="number" class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800 border-none px-6 font-bold dark:text-white" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-3 px-1">Mô tả chi tiết</label>
                                <textarea
                                    v-model="form.description"
                                    class="w-full px-6 py-5 bg-slate-50 dark:bg-slate-800 border-none rounded-[2rem] focus:outline-none focus:ring-2 focus:ring-blue-600/20 font-medium h-40 dark:text-white scrollbar-hide"
                                    placeholder="Tóm tắt ngắn gọn nội dung cuốn sách..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-8 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3 bg-slate-50/30 dark:bg-slate-800/20">
                    <Button @click="$emit('close')" variant="outline" class="h-14 rounded-2xl px-10 border-slate-200 dark:border-slate-700 font-bold dark:text-slate-300">
                        Hủy bỏ
                    </Button>
                    <Button @click="$emit('submit')" class="h-14 rounded-2xl px-10 bg-blue-600 hover:bg-blue-700 text-white shadow-xl shadow-blue-600/30 font-black uppercase tracking-widest">
                        {{ isEditing ? 'Lưu cập nhật' : 'Thêm vào kho' }}
                    </Button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
