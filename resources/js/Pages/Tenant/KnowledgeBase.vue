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
import { Plus, Pencil, Trash2, Database, Upload, FileText, File } from 'lucide-vue-next';

const props = defineProps({ entries: Array });

const showNew = ref(false);
const addMode = ref('text'); // 'text' | 'document'
const editingId = ref(null);
const dragOver = ref(false);

const form = useForm({ title: '', content: '' });
const uploadForm = useForm({ title: '', type: 'document', file: null });
const editForm = useForm({ title: '', content: '', is_active: true });

function addEntry() {
  form.post('/knowledge-base', { onSuccess: () => { form.reset(); showNew.value = false; } });
}

function uploadDocument() {
  uploadForm.post('/knowledge-base/upload', {
    forceFormData: true,
    onSuccess: () => { uploadForm.reset(); showNew.value = false; },
  });
}

function onFileChange(e) {
  const file = e.target.files[0];
  if (file) {
    uploadForm.file = file;
    if (!uploadForm.title) uploadForm.title = file.name.replace(/\.[^.]+$/, '');
  }
}

function onDrop(e) {
  dragOver.value = false;
  const file = e.dataTransfer.files[0];
  if (file) {
    uploadForm.file = file;
    if (!uploadForm.title) uploadForm.title = file.name.replace(/\.[^.]+$/, '');
  }
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

function fileIcon(entry) {
  if (entry.type !== 'document') return null;
  const ext = entry.file_name?.split('.').pop()?.toLowerCase();
  return ext === 'pdf' ? File : FileText;
}

function cancelNew() {
  showNew.value = false;
  form.reset();
  uploadForm.reset();
}
</script>

<template>
  <TenantLayout>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Knowledge Base</h1>
        <p class="text-muted-foreground text-sm mt-1">
          Entries here are appended to the AI system prompt automatically. Add text entries or upload documents.
        </p>
      </div>
      <Button @click="showNew = !showNew">
        <Plus class="w-4 h-4" /> Add Entry
      </Button>
    </div>

    <!-- New entry form -->
    <Card v-if="showNew" class="mb-6 border-primary/30">
      <CardHeader>
        <div class="flex items-center gap-4">
          <CardTitle>New Knowledge Entry</CardTitle>
          <div class="flex rounded-md border overflow-hidden text-sm">
            <button
              class="px-3 py-1 transition-colors"
              :class="addMode === 'text' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted'"
              @click="addMode = 'text'"
            >Text</button>
            <button
              class="px-3 py-1 transition-colors"
              :class="addMode === 'document' ? 'bg-primary text-primary-foreground' : 'bg-background hover:bg-muted'"
              @click="addMode = 'document'"
            >Upload Document</button>
          </div>
        </div>
      </CardHeader>

      <!-- Text entry -->
      <template v-if="addMode === 'text'">
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
          <Button variant="outline" @click="cancelNew">Cancel</Button>
        </CardFooter>
      </template>

      <!-- Document upload -->
      <template v-else>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label>Title</Label>
            <Input v-model="uploadForm.title" placeholder="e.g. Product Catalog" />
            <p v-if="uploadForm.errors.title" class="text-xs text-destructive">{{ uploadForm.errors.title }}</p>
          </div>
          <div class="space-y-1.5">
            <Label>Document</Label>
            <div
              class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
              :class="dragOver ? 'border-primary bg-primary/5' : 'border-muted-foreground/30 hover:border-primary/50'"
              @dragover.prevent="dragOver = true"
              @dragleave="dragOver = false"
              @drop.prevent="onDrop"
              @click="$refs.fileInput.click()"
            >
              <Upload class="w-8 h-8 mx-auto mb-2 text-muted-foreground" />
              <p class="text-sm font-medium">
                {{ uploadForm.file ? uploadForm.file.name : 'Drop a file here or click to browse' }}
              </p>
              <p class="text-xs text-muted-foreground mt-1">PDF, DOCX, TXT — up to 10 MB</p>
              <input ref="fileInput" type="file" class="hidden" accept=".pdf,.docx,.txt,.md" @change="onFileChange" />
            </div>
            <p v-if="uploadForm.errors.file" class="text-xs text-destructive">{{ uploadForm.errors.file }}</p>
          </div>
          <p class="text-xs text-muted-foreground">
            Text will be extracted from the document and injected into the AI prompt. The original file is kept for reference.
          </p>
        </CardContent>
        <CardFooter class="gap-2">
          <Button @click="uploadDocument" :disabled="uploadForm.processing || !uploadForm.file">
            <Upload class="w-4 h-4" />
            {{ uploadForm.processing ? 'Uploading…' : 'Upload & Extract' }}
          </Button>
          <Button variant="outline" @click="cancelNew">Cancel</Button>
        </CardFooter>
      </template>
    </Card>

    <!-- Empty state -->
    <div v-if="!entries.length && !showNew" class="text-center py-20">
      <Database class="w-10 h-10 mx-auto text-muted-foreground mb-3" />
      <p class="text-muted-foreground">No knowledge base entries yet.</p>
      <p class="text-sm text-muted-foreground mt-1">Add your services, pricing, FAQs — or upload a document.</p>
    </div>

    <!-- Entries -->
    <div class="space-y-3">
      <Card v-for="entry in entries" :key="entry.id">
        <template v-if="editingId !== entry.id">
          <CardHeader class="pb-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <component :is="fileIcon(entry) ?? Database" class="w-4 h-4 text-muted-foreground shrink-0" />
                <CardTitle class="text-base">{{ entry.title }}</CardTitle>
                <Badge v-if="entry.type === 'document'" variant="outline" class="text-xs">
                  {{ entry.file_name?.split('.').pop()?.toUpperCase() }}
                </Badge>
                <Badge :variant="entry.is_active ? 'success' : 'secondary'">
                  {{ entry.is_active ? 'Active' : 'Disabled' }}
                </Badge>
              </div>
              <div class="flex gap-1">
                <Button variant="ghost" size="icon" @click="startEdit(entry)"><Pencil class="w-4 h-4" /></Button>
                <Button variant="ghost" size="icon" @click="remove(entry)"><Trash2 class="w-4 h-4 text-destructive" /></Button>
              </div>
            </div>
            <p v-if="entry.file_name" class="text-xs text-muted-foreground ml-7 mt-0.5">{{ entry.file_name }}</p>
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
              <Label>Content <span v-if="entry.type === 'document'" class="text-xs text-muted-foreground">(extracted text)</span></Label>
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
