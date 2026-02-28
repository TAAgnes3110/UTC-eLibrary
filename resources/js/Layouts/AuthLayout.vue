<script setup>
import { useColorMode } from '@vueuse/core'

</script>

<template>
    <div
        class="relative min-h-screen min-h-[100dvh] w-full flex flex-col items-center font-sans py-6 px-4 pb-[env(safe-area-inset-bottom)] pt-[env(safe-area-inset-top)] sm:py-10 sm:px-6"
    >
        <div
            class="fixed inset-0 z-0 h-full w-full bg-cover bg-center bg-no-repeat"
            style="background-image: url('/Image/utc_bg_v2.png');"
        >
            <div class="absolute inset-0 bg-black/15 backdrop-blur-[0.5px]" />
        </div>
        <div class="relative z-10 w-full max-w-4xl min-w-0 flex-1 flex flex-col items-center justify-center py-4 sm:p-6">
            <slot />
        </div>
    </div>
</template>
