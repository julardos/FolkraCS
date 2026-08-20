<script setup>
import { buttonVariants } from './index.js';
import { cn } from '@/lib/utils';

const props = defineProps({
  variant: { type: String, default: 'default' },
  size: { type: String, default: 'default' },
  class: { type: String, default: '' },
  disabled: Boolean,
});
</script>

<template>
  <button
    :class="cn(buttonVariants({ variant, size }), props.class)"
    :disabled="disabled"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>
