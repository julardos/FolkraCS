<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Textarea from '@/components/ui/textarea/Textarea.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Plus, Pencil, Trash2, Database } from 'lucide-vue-next';

const props = defineProps({ entries: Array });

const showNew = ref(false);
const editingId = ref(null);

const form = useForm({ title: '', content: '' });
const editForm = useForm({ title: '', content: '', is_active: true });

function addEntry() {
  form.post('/knowledge-base', { onSuccess: () => { form.reset(); showNew.value = false; } });
}

function startEdit(entry) {
  editingId.value = entry.id;
  editForm.defaults({ title: entry.title, content: entry.content, is_active: entry.is_active }).reset();
}

function saveEdit(entry) {
  editForm.put(`/knowledge-base/${entry.id}`, { onSuccess: () => { editingId.value = null; } });
}

function remove(entry) {
  if (confirm('Delete this entry?')) router.delete(`/knowledge-base/${entry.id}`);
}
</script>

<template>
  <TenantLayout>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Knowledge Base</h1>
        <p class="text-muted-foreground text-sm mt-1">
          Entries here are appended to the AI system prompt automatically. Use it for your catalog, FAQs, pricing, etc.
        </p>
      </div>
      <Button @click="showNew = !showNew">
        <Plus class="w-4 h-4" /> Add Entry
      </Button>
    </div>

    <!-- New entry form -->
    <Card v-if="showNew" class="mb-6 border-primary/30">
      <CardHeader><CardTitle>New Knowledge Entry</CardTitle></CardHeader>
      <CardContent class="space-y-4">
        <div class="space-y-1.5">
          <Label>Title</Label>
          <Input v-model="form.title" placeholder="e.g. Services & Pricing" />
          <p v-if="form.errors.title" class="text-xs text-destructive">{{ form.errors.title }}</p>
        </div>
        <div class="space-y-1.5">
          <Label>Content</Label>
          <Textarea v-model="form.content" :rows="8" placeholder="Enter the knowledge content here..." class="font-mono text-sm" />
          <p v-if="form.errors.content" class="text-xs text-destructive">{{ form.errors.content }}</p>
        </div>
      </CardContent>
      <CardFooter class="gap-2">
        <Button @click="addEntry" :disabled="form.processing">Add Entry</Button>
        <Button variant="outline" @click="showNew = false">Cancel</Button>
      </CardFooter>
    </Card>

    <!-- Empty state -->
    <div v-if="!entries.length && !showNew" class="text-center py-20">
      <Database class="w-10 h-10 mx-auto text-muted-foreground mb-3" />
      <p class="text-muted-foreground">No knowledge base entries yet.</p>
      <p class="text-sm text-muted-foreground mt-1">Add your services, pricing, FAQs — whatever the AI needs to know.</p>
    </div>

    <!-- Entries -->
    <div class="space-y-3">
      <Card v-for="entry in entries" :key="entry.id">
        <template v-if="editingId !== entry.id">
          <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <CardTitle class="text-base">{{ entry.title }}</CardTitle>
                <Badge :variant="entry.is_active ? 'success' : 'secondary'">
                  {{ entry.is_active ? 'Active' : 'Disabled' }}
                </Badge>
              </div>
              <div class="flex gap-1">
                <Button variant="ghost" size="icon" @click="startEdit(entry)"><Pencil class="w-4 h-4" /></Button>
                <Button variant="ghost" size="icon" @click="remove(entry)"><Trash2 class="w-4 h-4 text-destructive" /></Button>
              </div>
            </div>
          </CardHeader>
          <CardContent class="pt-0">
            <pre class="text-xs text-muted-foreground whitespace-pre-wrap font-mono bg-muted/50 rounded p-3 max-h-40 overflow-auto">{{ entry.content }}</pre>
          </CardContent>
        </template>

        <template v-else>
          <CardHeader><CardTitle>Editing: {{ entry.title }}</CardTitle></CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label>Title</Label>
              <Input v-model="editForm.title" />
            </div>
            <div class="space-y-1.5">
              <Label>Content</Label>
              <Textarea v-model="editForm.content" :rows="10" class="font-mono text-sm" />
            </div>
            <div class="flex items-center gap-2">
              <input type="checkbox" v-model="editForm.is_active" id="is_active" class="rounded" />
              <label for="is_active" class="text-sm">Active (injected into AI prompt)</label>
            </div>
          </CardContent>
          <CardFooter class="gap-2">
            <Button @click="saveEdit(entry)" :disabled="editForm.processing">Save</Button>
            <Button variant="outline" @click="editingId = null">Cancel</Button>
          </CardFooter>
        </template>
      </Card>
    </div>
  </TenantLayout>
</template>
