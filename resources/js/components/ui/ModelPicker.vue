<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronDown, Search } from 'lucide-vue-next';

const props = defineProps({
  modelValue: { type: String, default: '' },
  models:     { type: Array, default: () => [] },
  placeholder:{ type: String, default: 'Select model…' },
});
const emit = defineEmits(['update:modelValue']);

const open      = ref(false);
const search    = ref('');
const container = ref(null);

const filtered = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) return props.models;
  return props.models.filter(m =>
    m.id.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
  );
});

const selectedName = computed(() =>
  props.models.find(m => m.id === props.modelValue)?.name ?? props.modelValue
);

function pick(m) {
  emit('update:modelValue', m.id);
  open.value  = false;
  search.value = '';
}

function onOutside(e) {
  if (container.value && !container.value.contains(e.target)) {
    open.value   = false;
    search.value = '';
  }
}

onMounted(()  => document.addEventListener('mousedown', onOutside));
onUnmounted(() => document.removeEventListener('mousedown', onOutside));
</script>

<template>
  <div class="relative" ref="container">
    <!-- Trigger button -->
    <button
      type="button"
      @click="open = !open"
      class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm hover:bg-accent transition-colors"
    >
      <span class="truncate text-left" :class="modelValue ? '' : 'text-muted-foreground'">
        {{ modelValue ? selectedName : placeholder }}
      </span>
      <ChevronDown class="w-4 h-4 shrink-0 text-muted-foreground transition-transform" :class="open ? 'rotate-180' : ''" />
    </button>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute z-30 mt-1 w-full rounded-md border bg-popover shadow-lg"
    >
      <!-- Search input -->
      <div class="flex items-center gap-2 border-b px-3 py-2">
        <Search class="w-3.5 h-3.5 shrink-0 text-muted-foreground" />
        <input
          v-model="search"
          autofocus
          placeholder="Cari model…"
          class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
        />
      </div>

      <!-- Options -->
      <div class="max-h-64 overflow-y-auto py-1">
        <p v-if="!models.length" class="px-3 py-4 text-xs text-center text-muted-foreground">
          Simpan API key terlebih dahulu untuk memuat daftar model.
        </p>
        <p v-else-if="!filtered.length" class="px-3 py-4 text-xs text-center text-muted-foreground">
          Tidak ada model ditemukan.
        </p>
        <button
          v-for="m in filtered"
          :key="m.id"
          type="button"
          @click="pick(m)"
          class="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-accent transition-colors"
          :class="m.id === modelValue ? 'bg-primary/5 font-medium' : ''"
        >
          <span>{{ m.name }}</span>
          <span class="text-xs text-muted-foreground font-normal">{{ m.id }}</span>
        </button>
      </div>
    </div>
  </div>
</template>
