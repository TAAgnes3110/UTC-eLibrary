<script setup>
import { ref } from 'vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    error: { type: String, default: '' },
    digits: { type: Number, default: 6 },
    size: { type: String, default: 'md', validator: v => ['sm', 'md'].includes(v) },
})

const emit = defineEmits(['update:modelValue'])

const otpDigits = ref(Array(props.digits).fill(''))
const inputRefs = ref([])

const syncValue = () => {
    emit('update:modelValue', otpDigits.value.join(''))
}

const handleInput = (index, event) => {
    const val = event.target.value.replace(/\D/g, '')
    otpDigits.value[index] = val
    syncValue()
    if (val && index < props.digits - 1) {
        inputRefs.value[index + 1]?.focus()
    }
}

const handlePaste = (index, event) => {
    event.preventDefault()
    const pasteData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, props.digits - index)
    if (!pasteData) return
    pasteData.split('').forEach((digit, i) => {
        if (index + i < props.digits) {
            otpDigits.value[index + i] = digit
        }
    })
    syncValue()
    const nextIndex = Math.min(index + pasteData.length, props.digits - 1)
    inputRefs.value[nextIndex]?.focus()
}

const handleKeyDown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        inputRefs.value[index - 1]?.focus()
    }
}

const reset = () => {
    otpDigits.value = Array(props.digits).fill('')
    syncValue()
}

defineExpose({ reset })
</script>

<template>
    <div>
        <div class="flex justify-between gap-2 sm:gap-3 px-1">
            <input
                v-for="(_, index) in otpDigits"
                :key="index"
                :ref="el => inputRefs[index] = el"
                type="text"
                v-model="otpDigits[index]"
                maxlength="1"
                inputmode="numeric"
                @input="handleInput(index, $event)"
                @keydown="handleKeyDown(index, $event)"
                @paste="handlePaste(index, $event)"
                class="w-full text-center font-bold rounded-xl border-2 border-white/5 bg-white/5 text-white focus:bg-blue-600/10 focus:border-blue-500/40 focus:ring-0 transition-all duration-200 outline-none shadow-lg"
                :class="[
                    error ? 'border-red-500/40 bg-red-500/5' : '',
                    size === 'sm' ? 'h-11 text-lg' : 'h-14 text-xl',
                ]"
            />
        </div>
        <p v-if="error" class="text-[11px] text-red-500 font-bold uppercase tracking-wider text-center animate-pulse mt-2">
            {{ error }}
        </p>
    </div>
</template>
