<script setup>
import { Primitive } from 'radix-vue'
import { cva } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const buttonVariants = cva(
  'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-primary/90',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        outline: 'border border-input bg-background hover:bg-accent hover:text-accent-foreground',
        secondary: 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        ghost: 'hover:bg-accent hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 hover:underline',
        // Biến thể "text" theo yêu cầu (giống ghost nhưng có thể tùy chỉnh thêm nếu cần)
        text: 'bg-transparent text-primary hover:bg-accent/10 hover:underline',
      },
      size: {
        default: 'h-10 px-4 py-2 text-medium',
        sm: 'h-9 rounded-md px-3 text-small',
        lg: 'h-11 rounded-md px-8',
        icon: 'h-10 w-10',

        small: 'h-8 rounded-md px-3 text-small',
        medium: 'h-10 px-5 py-2.5 text-medium',
        big: 'h-12 rounded-md px-10 text-big',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'medium',
    },
  },
)

const props = defineProps({
  variant: { type: String, required: false },
  size: { type: String, required: false },
  as: { type: String, default: 'button' },
  class: { type: String, required: false },
  asChild: { type: Boolean, required: false },
})
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(buttonVariants({ variant, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>
