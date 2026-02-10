<script setup>
import { useColorMode } from '@vueuse/core'

</script>

<template>
    <div class="relative min-h-screen w-full flex flex-col items-center font-sans py-10 px-4">
        <!-- Background Image & Overlay -->
        <div
            class="fixed inset-0 z-0 h-full w-full bg-cover bg-center bg-no-repeat"
            style="background-image: url('/Image/utc_bg_v2.png');"
        >
            <div class="absolute inset-0 bg-black/15 backdrop-blur-[0.5px]" />
        </div>

        <!-- Central Content Wrapper -->
        <div class="relative z-10 w-full max-w-4xl p-4 flex flex-col items-center my-auto">
            <slot />
        </div>
    </div>
</template>
