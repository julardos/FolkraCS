<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Wifi, WifiOff, Bot, Key, ChevronDown, ChevronUp, ExternalLink } from 'lucide-vue-next';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import ModelPicker from '@/components/ui/ModelPicker.vue';

const props = defineProps({ clients: Array, models: Array });

// ── New client form ──────────────────────────────────────────
const showNewForm = ref(false);
const form = useForm({
  name: '', business_type: '',
  // initial admin user to create for this client
  admin_name: '', admin_email: '',
  wa_base_url: '', wa_api_key: '', wa_session: '',
  openrouter_api_key: '', openrouter_model: 'openai/gpt-4o-mini',
  ai_instruction: '',
});

// Local client-side validation errors (shown immediately to the user)
const localErrors = ref({});

function validateForm() {
  localErrors.value = {};
  if (!form.name || !form.name.trim()) {
    localErrors.value.name = 'Client name is required.';
  }
  if (!form.admin_name || !form.admin_name.trim()) {
    localErrors.value.admin_name = 'Admin name is required.';
  }
  if (!form.admin_email || !form.admin_email.trim()) {
    localErrors.value.admin_email = 'Admin email is required.';
  } else {
    const re = /^\S+@\S+\.\S+$/;
    if (!re.test(form.admin_email)) {
      localErrors.value.admin_email = 'Please enter a valid email address.';
    }
  }

  if (form.wa_base_url && form.wa_base_url.trim()) {
    try {
      new URL(form.wa_base_url);
    } catch (e) {
      localErrors.value.wa_base_url = 'Please enter a valid URL.';
    }
  }

  return Object.keys(localErrors.value).length === 0;
}

function submit() {
  // clear server-side errors before validating
  form.clearErrors && form.clearErrors();

  if (!validateForm()) {
    // don't submit, localErrors will be shown
    return;
  }

  form.post('/clients', {
    onSuccess: () => {
      form.reset();
      showNewForm.value = false;
      localErrors.value = {};
    },
    onError: () => {
      // server-side validation errors populate form.errors automatically
    }
  });
}

// ── Edit ─────────────────────────────────────────────────────
const editingId = ref(null);
const editForm = useForm({});

function startEdit(client) {
  editingId.value = client.id;
  editForm.defaults({
    name: client.name, business_type: client.business_type ?? '',
    status: client.status,
    wa_base_url: client.wa_base_url ?? '', wa_api_key: '', wa_session: client.wa_session ?? '',
    openrouter_api_key: '', openrouter_model: client.openrouter_model ?? 'openai/gpt-4o-mini',
    ai_instruction: client.ai_instruction ?? '',
  }).reset();
}

function saveEdit(client) {
  editForm.put(`/clients/${client.id}`, { onSuccess: () => { editingId.value = null; } });
}

function destroy(client) {
  if (confirm(`Remove ${client.name}?`)) {
    router.delete(`/clients/${client.id}`);
  }
}

const statusVariant = (s) => ({ active: 'success', inactive: 'secondary', suspended: 'destructive' }[s] ?? 'outline');

// models come from the server (OpenRouter API, cached 1h)

// expanded instruction panels
const expandedInstruction = ref(null);
</script>

<template>
  <AppLayout>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Clients</h1>
        <p class="text-muted-foreground text-sm mt-1">Manage your client WA bots and AI configuration.</p>
      </div>
      <Button @click="showNewForm = !showNewForm">
        <Plus class="w-4 h-4" />
        Add Client
      </Button>
    </div>

    <!-- New client form -->
    <Card v-if="showNewForm" class="mb-6 border-primary/30">
      <CardHeader>
        <CardTitle>New Client</CardTitle>
        <CardDescription>Set up a new client's WA bot and AI settings.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <div v-if="form.errors.server" class="p-3 rounded bg-red-50 text-red-700 text-sm">{{ form.errors.server }}</div>
        <!-- Basic info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label>Client Name *</Label>
            <Input v-model="form.name" placeholder="Sejukin Indonesia" />
            <p v-if="form.errors.name || localErrors.name" class="text-xs text-destructive">{{ form.errors.name || localErrors.name }}</p>
          </div>
          <div class="space-y-1.5">
            <Label>Business Type</Label>
            <Input v-model="form.business_type" placeholder="AC Service, Restaurant, etc." />
          </div>
        </div>

        <!-- Admin user -->
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label>Admin Name *</Label>
            <Input v-model="form.admin_name" placeholder="Admin Name" />
            <p v-if="form.errors.admin_name || localErrors.admin_name" class="text-xs text-destructive">{{ form.errors.admin_name || localErrors.admin_name }}</p>
          </div>
          <div class="space-y-1.5">
            <Label>Admin Email *</Label>
            <Input v-model="form.admin_email" placeholder="admin@example.com" />
            <p v-if="form.errors.admin_email || localErrors.admin_email" class="text-xs text-destructive">{{ form.errors.admin_email || localErrors.admin_email }}</p>
          </div>
        </div>

        <Separator />

        <!-- WA Config -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <Wifi class="w-4 h-4 text-green-600" />
            <span class="text-sm font-semibold">WhatsApp Config</span>
          </div>
          <div class="grid grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label>WAHA Base URL</Label>
              <Input v-model="form.wa_base_url" placeholder="https://wa.example.com" />
              <p v-if="form.errors.wa_base_url || localErrors.wa_base_url" class="text-xs text-destructive">{{ form.errors.wa_base_url || localErrors.wa_base_url }}</p>
            </div>
            <div class="space-y-1.5">
              <Label>API Key</Label>
              <Input v-model="form.wa_api_key" type="password" placeholder="waha-api-key" />
              <p v-if="form.errors.wa_api_key" class="text-xs text-destructive">{{ form.errors.wa_api_key }}</p>
            </div>
            <div class="space-y-1.5">
              <Label>Session Name</Label>
              <Input v-model="form.wa_session" placeholder="sejukin-indonesia" />
              <p v-if="form.errors.wa_session" class="text-xs text-destructive">{{ form.errors.wa_session }}</p>
            </div>
          </div>
        </div>

        <Separator />

        <!-- AI Config -->
        <div>
          <div class="flex items-center gap-2 mb-3">
            <Bot class="w-4 h-4 text-blue-600" />
            <span class="text-sm font-semibold">AI Config (OpenRouter)</span>
          </div>
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="space-y-1.5">
              <Label>OpenRouter API Key</Label>
              <Input v-model="form.openrouter_api_key" type="password" placeholder="sk-or-..." />
              <p v-if="form.errors.openrouter_api_key" class="text-xs text-destructive">{{ form.errors.openrouter_api_key }}</p>
            </div>
            <div class="space-y-1.5">
              <Label>Model</Label>
              <ModelPicker v-model="form.openrouter_model" :models="models" />
            </div>
          </div>
          <div class="space-y-1.5">
            <Label>Brief AI Instruction</Label>
            <Textarea v-model="form.ai_instruction" :rows="6" placeholder="You are [Bot Name], a customer service assistant for [Company]...&#10;&#10;IDENTITY: ...&#10;LANGUAGE: ...&#10;TOPICS: ..." />
            <p v-if="form.errors.ai_instruction" class="text-xs text-destructive">{{ form.errors.ai_instruction }}</p>
            <p class="text-xs text-muted-foreground">This is the system prompt the AI will follow for this client.</p>
          </div>
        </div>
      </CardContent>
      <CardFooter class="gap-2">
        <Button @click="submit" :disabled="form.processing">Save Client</Button>
        <Button variant="outline" @click="showNewForm = false">Cancel</Button>
      </CardFooter>
    </Card>

    <!-- Empty state -->
    <div v-if="!clients.length" class="text-center py-20">
      <Bot class="w-12 h-12 mx-auto text-muted-foreground mb-3" />
      <p class="text-muted-foreground">No clients yet. Add your first client to get started.</p>
    </div>

    <!-- Client cards -->
    <div class="space-y-4">
      <Card v-for="client in clients" :key="client.id">
        <!-- View mode -->
        <template v-if="editingId !== client.id">
          <CardHeader class="pb-3">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center font-bold text-primary">
                  {{ client.name.charAt(0) }}
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <CardTitle class="text-base">{{ client.name }}</CardTitle>
                    <Badge :variant="statusVariant(client.status)">{{ client.status }}</Badge>
                  </div>
                  <CardDescription>{{ client.business_type ?? '—' }} · Added {{ client.created_at }}</CardDescription>
                </div>
              </div>
              <div class="flex gap-1">
                <Button variant="ghost" size="icon" @click="startEdit(client)">
                  <Pencil class="w-4 h-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="destroy(client)">
                  <Trash2 class="w-4 h-4 text-destructive" />
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent class="pt-0">
            <div class="grid grid-cols-3 gap-4 text-sm">
              <!-- WA -->
              <div class="rounded-lg bg-muted/50 p-3 space-y-1.5">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                  <Wifi class="w-3 h-3" /> WhatsApp
                </div>
                <p class="font-medium">{{ client.wa_session ?? '—' }}</p>
                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                  <span>{{ client.wa_base_url ?? 'No URL set' }}</span>
                  <a v-if="client.wa_base_url" :href="client.wa_base_url" target="_blank">
                    <ExternalLink class="w-3 h-3" />
                  </a>
                </div>
                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                  <Key class="w-3 h-3" /> {{ client.masked_wa_key }}
                </div>
              </div>

              <!-- AI -->
              <div class="rounded-lg bg-muted/50 p-3 space-y-1.5">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                  <Bot class="w-3 h-3" /> AI (OpenRouter)
                </div>
                <p class="font-medium text-xs">{{ client.openrouter_model }}</p>
                <div class="flex items-center gap-1 text-xs text-muted-foreground">
                  <Key class="w-3 h-3" /> {{ client.masked_ai_key }}
                </div>
              </div>

              <!-- Webhook -->
              <div class="rounded-lg bg-muted/50 p-3 space-y-1.5">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                  Webhook URL
                </div>
                <p class="font-mono text-xs break-all text-muted-foreground">
                  POST /api/webhook
                </p>
                <p class="text-xs text-muted-foreground">Session: {{ client.wa_session ?? '—' }}</p>
              </div>
            </div>

            <!-- AI Instruction preview -->
            <div v-if="client.ai_instruction" class="mt-3">
              <button
                class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
                @click="expandedInstruction = expandedInstruction === client.id ? null : client.id"
              >
                <component :is="expandedInstruction === client.id ? ChevronUp : ChevronDown" class="w-3 h-3" />
                AI Instruction
              </button>
              <div v-if="expandedInstruction === client.id" class="mt-2 rounded-md bg-muted/50 p-3">
                <pre class="text-xs text-muted-foreground whitespace-pre-wrap font-mono">{{ client.ai_instruction }}</pre>
              </div>
            </div>
            <div v-else class="mt-3 text-xs text-amber-600 flex items-center gap-1">
              ⚠ No AI instruction set — bot will have no persona.
            </div>
          </CardContent>
        </template>

        <!-- Edit mode -->
        <template v-else>
          <CardHeader>
            <CardTitle>Editing: {{ client.name }}</CardTitle>
          </CardHeader>
          <CardContent class="space-y-5">
            <div class="grid grid-cols-3 gap-4">
              <div class="space-y-1.5">
                <Label>Client Name *</Label>
                <Input v-model="editForm.name" />
              </div>
              <div class="space-y-1.5">
                <Label>Business Type</Label>
                <Input v-model="editForm.business_type" />
              </div>
              <div class="space-y-1.5">
                <Label>Status</Label>
                <select v-model="editForm.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>
            </div>

            <Separator />

            <div class="flex items-center gap-2 mb-1">
              <Wifi class="w-4 h-4 text-green-600" />
              <span class="text-sm font-semibold">WhatsApp Config</span>
            </div>
            <div class="grid grid-cols-3 gap-4">
              <div class="space-y-1.5">
                <Label>WAHA Base URL</Label>
                <Input v-model="editForm.wa_base_url" />
              </div>
              <div class="space-y-1.5">
                <Label>API Key <span class="text-muted-foreground">(leave blank to keep)</span></Label>
                <Input v-model="editForm.wa_api_key" type="password" placeholder="••••" />
              </div>
              <div class="space-y-1.5">
                <Label>Session Name</Label>
                <Input v-model="editForm.wa_session" />
              </div>
            </div>

            <Separator />

            <div class="flex items-center gap-2 mb-1">
              <Bot class="w-4 h-4 text-blue-600" />
              <span class="text-sm font-semibold">AI Config</span>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label>OpenRouter API Key <span class="text-muted-foreground">(leave blank to keep)</span></Label>
                <Input v-model="editForm.openrouter_api_key" type="password" placeholder="••••" />
              </div>
              <div class="space-y-1.5">
                <Label>Model</Label>
                <ModelPicker v-model="editForm.openrouter_model" :models="models" />
              </div>
            </div>
            <div class="space-y-1.5">
              <Label>Brief AI Instruction</Label>
              <Textarea v-model="editForm.ai_instruction" :rows="8" />
            </div>
          </CardContent>
          <CardFooter class="gap-2">
            <Button @click="saveEdit(client)" :disabled="editForm.processing">Save Changes</Button>
            <Button variant="outline" @click="editingId = null">Cancel</Button>
          </CardFooter>
        </template>
      </Card>
    </div>
  </AppLayout>
</template>
