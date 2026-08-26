<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { CheckCircle2, Circle, ArrowRight, Rocket } from 'lucide-vue-next';

const props = defineProps({ steps: Array });

const completedCount = computed(() => props.steps.filter(s => s.done).length);
const progressPct   = computed(() => Math.round((completedCount.value / props.steps.length) * 100));
const allDone       = computed(() => completedCount.value === props.steps.length);

// First incomplete step is the "current" one — highlighted
const currentKey = computed(() => props.steps.find(s => !s.done)?.key ?? null);
</script>

<template>
  <TenantLayout>
    <div class="max-w-2xl">

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-1">
          <Rocket class="w-6 h-6 text-primary" />
          <h1 class="text-2xl font-bold tracking-tight">Mulai</h1>
        </div>
        <p class="text-muted-foreground text-sm">
          Selesaikan langkah-langkah berikut untuk mengaktifkan asisten AI Anda.
        </p>
      </div>

      <!-- Progress bar -->
      <div class="mb-8">
        <div class="flex items-center justify-between text-xs text-muted-foreground mb-2">
          <span>{{ completedCount }} dari {{ steps.length }} selesai</span>
          <span>{{ progressPct }}%</span>
        </div>
        <div class="h-2 bg-muted rounded-full overflow-hidden">
          <div
            class="h-full bg-primary rounded-full transition-all duration-500"
            :style="{ width: progressPct + '%' }"
          />
        </div>
      </div>

      <!-- All done banner -->
      <div v-if="allDone" class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 rounded-lg px-5 py-4">
        <CheckCircle2 class="w-5 h-5 text-green-600 shrink-0" />
        <div>
          <p class="text-sm font-semibold text-green-800">Semuanya siap!</p>
          <p class="text-xs text-green-600 mt-0.5">Asisten AI Anda sudah aktif dan siap melayani pelanggan.</p>
        </div>
      </div>

      <!-- Steps -->
      <ol class="space-y-3">
        <li
          v-for="(step, index) in steps"
          :key="step.key"
          class="rounded-lg border transition-colors"
          :class="step.key === currentKey
            ? 'border-primary/40 bg-primary/5'
            : 'border-border bg-card'"
        >
          <div class="flex items-start gap-4 p-5">

            <!-- Icon -->
            <div class="shrink-0 mt-0.5">
              <CheckCircle2 v-if="step.done" class="w-5 h-5 text-green-500" />
              <div v-else-if="step.key === currentKey"
                   class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center">
                <span class="text-[10px] font-bold text-primary">{{ index + 1 }}</span>
              </div>
              <div v-else
                   class="w-5 h-5 rounded-full border-2 border-muted-foreground/30 flex items-center justify-center">
                <span class="text-[10px] font-medium text-muted-foreground/50">{{ index + 1 }}</span>
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold"
                 :class="step.done ? 'text-muted-foreground line-through' : 'text-foreground'">
                {{ step.label }}
              </p>
              <p class="text-xs text-muted-foreground mt-1 leading-relaxed">{{ step.desc }}</p>

              <!-- CTA button for current step -->
              <Link
                v-if="step.href && step.key === currentKey"
                :href="step.href"
                class="inline-flex items-center gap-1.5 mt-3 text-xs font-medium text-primary hover:underline"
              >
                {{ step.action }}
                <ArrowRight class="w-3.5 h-3.5" />
              </Link>
            </div>

            <!-- Done badge -->
            <span v-if="step.done" class="shrink-0 text-xs text-green-600 font-medium mt-0.5">Done</span>
          </div>
        </li>
      </ol>

      <!-- Link to re-visit any step once all are done -->
      <p v-if="allDone" class="mt-6 text-xs text-muted-foreground text-center">
        Ingin mengubah sesuatu?
        <Link href="/connections" class="text-primary hover:underline">Koneksi</Link> ·
        <Link href="/knowledge-base" class="text-primary hover:underline">Knowledge Base</Link> ·
        <Link href="/ai-settings" class="text-primary hover:underline">Pengaturan AI</Link>
      </p>

    </div>
  </TenantLayout>
</template>
