<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
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
import Separator from '@/components/ui/separator/Separator.vue';
import ModelPicker from '@/components/ui/ModelPicker.vue';
import { Key, Bot } from 'lucide-vue-next';

const props = defineProps({ client: Object, models: Array });

const form = useForm({
  openrouter_model:   props.client.openrouter_model,
  openrouter_api_key: '',
  ai_instruction:     props.client.ai_instruction ?? '',
});

function save() {
  form.put('/ai-settings');
}
</script>

<template>
  <TenantLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">AI Settings</h1>
      <p class="text-muted-foreground text-sm mt-1">Configure your AI model, API key, and bot persona.</p>
    </div>

    <div class="max-w-3xl space-y-6">
      <!-- Model & Key -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><Bot class="w-4 h-4" /> OpenRouter Config</CardTitle>
          <CardDescription>Select the AI model and set your API key.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label>Model</Label>
            <ModelPicker v-model="form.openrouter_model" :models="models" />
          </div>
          <div class="space-y-1.5">
            <Label class="flex items-center gap-2">
              <Key class="w-3 h-3" /> API Key
              <span class="text-muted-foreground font-normal">(leave blank to keep current: {{ client.masked_ai_key }})</span>
            </Label>
            <Input v-model="form.openrouter_api_key" type="password" placeholder="sk-or-..." />
            <p v-if="form.errors.openrouter_api_key" class="text-xs text-destructive">{{ form.errors.openrouter_api_key }}</p>
          </div>
        </CardContent>
      </Card>

      <Separator />

      <!-- AI Instruction -->
      <Card>
        <CardHeader>
          <CardTitle>Bot Instruction (System Prompt)</CardTitle>
          <CardDescription>
            Write what you want the AI to be. This is injected as the system prompt at the start of every conversation.
            Be specific: give it a name, a role, rules, and what it should/shouldn't do.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Textarea
            v-model="form.ai_instruction"
            :rows="20"
            placeholder="You are [Bot Name], a customer service assistant for [Company Name].

IDENTITY
You are helpful, friendly, and professional...

LANGUAGE RULES
Always respond in Indonesian...

TOPICS
Only answer questions about...

ESCALATION
If the customer is angry or asks to speak to a human, end your response with:
%%KENDALA_DETECTED%%
{ ... }
%%END_KENDALA%%"
            class="font-mono text-sm"
          />
          <p class="text-xs text-muted-foreground mt-2">
            Use <code class="bg-muted px-1 rounded">{{date}}</code>, <code class="bg-muted px-1 rounded">{{time}}</code>, <code class="bg-muted px-1 rounded">{{day}}</code> as placeholders — they're injected at runtime.
          </p>
        </CardContent>
        <CardFooter>
          <Button @click="save" :disabled="form.processing">Save Settings</Button>
        </CardFooter>
      </Card>
    </div>
  </TenantLayout>
</template>
