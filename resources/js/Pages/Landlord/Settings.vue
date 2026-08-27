<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
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
import { Key, Bot, Wifi, Globe } from 'lucide-vue-next';
import ModelPicker from '@/components/ui/ModelPicker.vue';

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
            <ModelPicker v-model="form.ai_model" :models="models" />
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
