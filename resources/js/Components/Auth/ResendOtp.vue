<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { RefreshCw } from 'lucide-vue-next'

const props = defineProps({
    loading: { type: Boolean, default: false },
    duration: { type: Number, default: 60 },
})

const emit = defineEmits(['resend'])

const countdown = ref(props.duration)
const canResend = ref(false)
let timer = null

const startTimer = () => {
    canResend.value = false
    countdown.value = props.duration
    if (timer) clearInterval(timer)
    timer = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--
        } else {
            canResend.value = true
            clearInterval(timer)
        }
    }, 1000)
}

const handleResend = () => {
    if (!canResend.value) return
    emit('resend')
    startTimer()
}

onMounted(startTimer)
onUnmounted(() => { if (timer) clearInterval(timer) })

defineExpose({ startTimer })
</script>

<template>
    <div class="flex items-center justify-center gap-2 bg-white/5 py-2 px-4 rounded-xl border border-white/5">
        <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest leading-none">Chưa nhận được mã?</p>
        <button
            type="button"
            @click="handleResend"
            :disabled="!canResend || loading"
            class="flex items-center gap-1.5 font-black text-[10px] transition-all uppercase tracking-wider leading-none"
            :class="canResend ? 'text-blue-400 hover:text-blue-300' : 'text-slate-600 cursor-not-allowed'"
        >
            <RefreshCw :size="12" :class="{ 'animate-spin': loading }" />
            <span>{{ canResend ? 'Gửi lại ngay' : `Thử lại sau ${countdown}s` }}</span>
        </button>
    </div>
</template>
