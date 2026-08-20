<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Plus, Trash2, UserPlus, Mail } from 'lucide-vue-next';

const props = defineProps({ users: Array });

const showForm = ref(false);
const form = useForm({ name: '', email: '' });

function invite() {
  form.post('/users', {
    onSuccess: () => { form.reset(); showForm.value = false; },
  });
}

function remove(user) {
  if (confirm(`Remove ${user.name} (${user.email})?`)) {
    router.delete(`/users/${user.id}`);
  }
}

const currentUserId = window.__page?.props?.auth?.user?.id;
</script>

<template>
  <TenantLayout>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Team Members</h1>
        <p class="text-muted-foreground text-sm mt-1">
          Admins can configure the bot, manage knowledge base, and view conversations.
        </p>
      </div>
      <Button @click="showForm = !showForm">
        <UserPlus class="w-4 h-4" /> Invite Member
      </Button>
    </div>

    <!-- Invite form -->
    <Card v-if="showForm" class="mb-6 border-primary/30 max-w-lg">
      <CardHeader>
        <CardTitle>Invite Team Member</CardTitle>
        <CardDescription>They'll receive an email to set their password.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="space-y-1.5">
          <Label>Name</Label>
          <Input v-model="form.name" placeholder="Full name" />
          <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
        </div>
        <div class="space-y-1.5">
          <Label>Email</Label>
          <Input v-model="form.email" type="email" placeholder="email@company.com" />
          <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
        </div>
      </CardContent>
      <CardFooter class="gap-2">
        <Button @click="invite" :disabled="form.processing">
          <Mail class="w-4 h-4" /> Send Invitation
        </Button>
        <Button variant="outline" @click="showForm = false">Cancel</Button>
      </CardFooter>
    </Card>

    <!-- User list -->
    <div class="space-y-2 max-w-2xl">
      <Card v-for="user in users" :key="user.id">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary text-sm">
                {{ user.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <p class="font-medium text-sm">{{ user.name }}</p>
                  <Badge variant="secondary" class="text-xs">{{ user.role }}</Badge>
                </div>
                <p class="text-xs text-muted-foreground">{{ user.email }} · Joined {{ user.created_at }}</p>
              </div>
            </div>
            <Button
              v-if="user.id !== $page.props.auth?.user?.id"
              variant="ghost"
              size="icon"
              @click="remove(user)"
            >
              <Trash2 class="w-4 h-4 text-destructive" />
            </Button>
            <span v-else class="text-xs text-muted-foreground pr-2">You</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </TenantLayout>
</template>
