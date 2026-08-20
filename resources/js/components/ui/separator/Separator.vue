<script setup>
import { cn } from '@/lib/utils';
const props = defineProps({ class: String, orientation: { type: String, default: 'horizontal' } });
</script>
<template>
  <div :class="cn('shrink-0 bg-border', orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]', props.class)" />
</template>
