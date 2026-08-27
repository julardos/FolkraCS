<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import { Key, Bot, Wifi, ChevronDown, Search, Globe } from 'lucide-vue-next';

const props = defineProps({ settings: Object, models: Array });

const form = useForm({
  wa_base_url:      props.settings.wa_base_url ?? '',
  ai_api_key:       '',
  ai_model:         props.settings.ai_model ?? 'openai/gpt-4o-mini',
  ai_system_prompt: props.settings.ai_system_prompt ?? '',
  timezone:         props.settings.timezone ?? 'Asia/Makassar',
});

const maskedKey = props.settings.ai_api_key
  ? '••••' + props.settings.ai_api_key.slice(-4)
  : 'not set';

// Searchable model picker
const dropdownOpen = ref(false);
const modelSearch  = ref('');
const dropdownEl   = ref(null);

const filteredModels = computed(() => {
  const q = modelSearch.value.trim().toLowerCase();
  if (!q) return props.models;
  return props.models.filter(m =>
    m.id.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
  );
});

const selectedModelName = computed(() =>
  props.models.find(m => m.id === form.ai_model)?.name ?? form.ai_model
);

function selectModel(m) {
  form.ai_model      = m.id;
  dropdownOpen.value = false;
  modelSearch.value  = '';
}

function onClickOutside(e) {
  if (dropdownEl.value && !dropdownEl.value.contains(e.target)) {
    dropdownOpen.value = false;
    modelSearch.value  = '';
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside));

function save() {
  form.put('/settings');
}
</script>

<template>
  <AppLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">Global Settings</h1>
      <p class="text-muted-foreground text-sm mt-1">
        Default values used for all tenants that haven't configured their own.
      </p>
    </div>

    <div class="max-w-3xl space-y-6">

      <!-- WhatsApp -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Wifi class="w-4 h-4" /> WhatsApp (WAHA)
          </CardTitle>
          <CardDescription>Default WAHA server URL used when a client hasn't set their own.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-1.5">
            <Label>WAHA Base URL</Label>
            <Input v-model="form.wa_base_url" placeholder="https://wa.yourserver.com" />
            <p v-if="form.errors.wa_base_url" class="text-xs text-destructive">{{ form.errors.wa_base_url }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- OpenRouter -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Bot class="w-4 h-4" /> OpenRouter — AI Default
          </CardTitle>
          <CardDescription>
            Default API key and model. Per-client settings override these.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">

          <div class="space-y-1.5">
            <Label class="flex items-center gap-2">
              <Key class="w-3 h-3" /> API Key
              <span class="text-muted-foreground font-normal">(current: {{ maskedKey }})</span>
            </Label>
            <Input v-model="form.ai_api_key" type="password" placeholder="sk-or-…  (leave blank to keep current)" />
          </div>

          <div class="space-y-1.5">
            <Label>Default Model</Label>
            <div v-if="models.length" class="relative" ref="dropdownEl">
              <button
                type="button"
                @click="dropdownOpen = !dropdownOpen"
                class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm hover:bg-accent transition-colors"
              >
                <span class="truncate">{{ selectedModelName }}</span>
                <ChevronDown class="w-4 h-4 text-muted-foreground shrink-0 ml-2" :class="dropdownOpen ? 'rotate-180' : ''" />
              </button>
              <div v-if="dropdownOpen" class="absolute z-20 mt-1 w-full rounded-md border bg-popover shadow-lg">
                <div class="flex items-center gap-2 border-b px-3 py-2">
                  <Search class="w-3.5 h-3.5 text-muted-foreground shrink-0" />
                  <input v-model="modelSearch" autofocus placeholder="Cari model…"
                    class="flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground" />
                </div>
                <div class="max-h-64 overflow-y-auto py-1">
                  <p v-if="!filteredModels.length" class="px-3 py-4 text-xs text-center text-muted-foreground">
                    Tidak ada model ditemukan.
                  </p>
                  <button v-for="m in filteredModels" :key="m.id" type="button" @click="selectModel(m)"
                    class="flex w-full flex-col px-3 py-2 text-left text-sm hover:bg-accent transition-colors"
                    :class="m.id === form.ai_model ? 'bg-primary/5 font-medium' : ''">
                    <span>{{ m.name }}</span>
                    <span class="text-xs text-muted-foreground">{{ m.id }}</span>
                  </button>
                </div>
              </div>
            </div>
            <div v-else>
              <Input v-model="form.ai_model" placeholder="openai/gpt-4o-mini" />
              <p class="text-xs text-muted-foreground mt-1">
                Save an API key first to load the model list from OpenRouter.
              </p>
            </div>
          </div>

        </CardContent>
      </Card>

      <!-- Default AI Instruction -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Bot class="w-4 h-4" /> Default AI Instruction
          </CardTitle>
          <CardDescription>
            Fallback system prompt for tenants that haven't written their own.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Textarea v-model="form.ai_system_prompt" :rows="10"
            placeholder="You are a helpful customer service assistant..." class="font-mono text-sm" />
        </CardContent>
      </Card>

      <!-- Timezone -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Globe class="w-4 h-4" /> Timezone
          </CardTitle>
          <CardDescription>Used for {{date}}, {{time}}, {{day}} placeholders in prompts.</CardDescription>
        </CardHeader>
        <CardContent>
          <Input v-model="form.timezone" placeholder="Asia/Makassar" />
          <p v-if="form.errors.timezone" class="text-xs text-destructive mt-1">{{ form.errors.timezone }}</p>
        </CardContent>
        <CardFooter>
          <Button @click="save" :disabled="form.processing">Save Settings</Button>
        </CardFooter>
      </Card>

    </div>
  </AppLayout>
</template>
