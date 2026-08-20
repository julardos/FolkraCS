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
import Separator from '@/components/ui/separator/Separator.vue';
import { Smartphone, Mail, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({ settings: Object });

const form = useForm({
  channel_wa:    props.settings.channel_wa,
  wa_number:     props.settings.wa_number ?? '',
  channel_email: props.settings.channel_email,
  email:         props.settings.email ?? '',
  notify_on:     props.settings.notify_on ?? [],
});

const kendalaTypes = [
  { value: 'complaint',       label: 'Complaint', desc: 'Customer unsatisfied with previous service' },
  { value: 'question',        label: 'Unanswerable Question', desc: 'AI couldn\'t answer from its knowledge base' },
  { value: 'escalation',      label: 'Escalation Request', desc: 'Customer explicitly asked to speak to a human' },
  { value: 'schedule_change', label: 'Schedule Change', desc: 'Customer wants to reschedule a confirmed booking' },
];

function toggle(val) {
  if (form.notify_on.includes(val)) {
    form.notify_on = form.notify_on.filter(v => v !== val);
  } else {
    form.notify_on = [...form.notify_on, val];
  }
}

function save() { form.put('/escalation'); }
</script>

<template>
  <TenantLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">Escalation Settings</h1>
      <p class="text-muted-foreground text-sm mt-1">
        Configure where to get notified when the AI detects something it can't handle.
      </p>
    </div>

    <div class="max-w-2xl space-y-6">
      <!-- Channels -->
      <Card>
        <CardHeader>
          <CardTitle>Notification Channels</CardTitle>
          <CardDescription>You can enable both — they fire simultaneously.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <!-- WA -->
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <input type="checkbox" v-model="form.channel_wa" id="ch_wa" class="rounded" />
              <label for="ch_wa" class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <Smartphone class="w-4 h-4 text-green-600" /> WhatsApp
              </label>
            </div>
            <div v-if="form.channel_wa" class="ml-6 space-y-1.5">
              <Label>Agent WA Number</Label>
              <Input v-model="form.wa_number" placeholder="6281234567890 (no + or spaces)" />
              <p class="text-xs text-muted-foreground">Must be different from the bot's session number.</p>
              <p v-if="form.errors.wa_number" class="text-xs text-destructive">{{ form.errors.wa_number }}</p>
            </div>
          </div>

          <Separator />

          <!-- Email -->
          <div class="space-y-3">
            <div class="flex items-center gap-3">
              <input type="checkbox" v-model="form.channel_email" id="ch_email" class="rounded" />
              <label for="ch_email" class="flex items-center gap-2 text-sm font-medium cursor-pointer">
                <Mail class="w-4 h-4 text-blue-600" /> Email
              </label>
            </div>
            <div v-if="form.channel_email" class="ml-6 space-y-1.5">
              <Label>Email Address</Label>
              <Input v-model="form.email" type="email" placeholder="admin@yourcompany.com" />
              <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Trigger types -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <AlertTriangle class="w-4 h-4" /> Notify On
          </CardTitle>
          <CardDescription>Which escalation types trigger a notification?</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
          <div
            v-for="type in kendalaTypes"
            :key="type.value"
            class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer hover:bg-accent/50 transition-colors"
            :class="form.notify_on.includes(type.value) ? 'border-primary bg-primary/5' : 'border-border'"
            @click="toggle(type.value)"
          >
            <input type="checkbox" :checked="form.notify_on.includes(type.value)" class="mt-0.5 rounded" @click.stop="toggle(type.value)" />
            <div>
              <p class="text-sm font-medium">{{ type.label }}</p>
              <p class="text-xs text-muted-foreground">{{ type.desc }}</p>
            </div>
          </div>
        </CardContent>
        <CardFooter>
          <Button @click="save" :disabled="form.processing">Save Settings</Button>
        </CardFooter>
      </Card>
    </div>
  </TenantLayout>
</template>
