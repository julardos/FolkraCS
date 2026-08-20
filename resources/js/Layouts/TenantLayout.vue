<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
  LayoutDashboard, Database, Bot, AlertTriangle, MessageSquare, LogOut, Users
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isAdmin = computed(() => user.value?.role === 'admin');

const nav = computed(() => [
  { label: 'Dashboard',      href: '/dashboard',      icon: LayoutDashboard, adminOnly: false },
  { label: 'Conversations',  href: '/conversations',  icon: MessageSquare,   adminOnly: false },
  { label: 'AI Settings',    href: '/ai-settings',    icon: Bot,             adminOnly: true },
  { label: 'Knowledge Base', href: '/knowledge-base', icon: Database,        adminOnly: true },
  { label: 'Escalation',     href: '/escalation',     icon: AlertTriangle,   adminOnly: true },
  { label: 'Team',           href: '/users',          icon: Users,           adminOnly: true },
].filter(item => !item.adminOnly || isAdmin.value));
</script>

<template>
  <div class="flex h-screen bg-background">
    <aside class="w-60 border-r bg-card flex flex-col">
      <div class="p-6 border-b">
        <div class="flex items-center gap-2">
          <Bot class="w-5 h-5 text-primary" />
          <span class="font-bold text-base tracking-tight">FolkraCS</span>
        </div>
        <p class="text-xs text-muted-foreground mt-1 truncate">{{ $page.props.clientName ?? 'Client Dashboard' }}</p>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <Link
          v-for="item in nav"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors"
          :class="$page.url.startsWith(item.href)
            ? 'bg-primary text-primary-foreground'
            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
        >
          <component :is="item.icon" class="w-4 h-4 shrink-0" />
          {{ item.label }}
        </Link>
      </nav>

      <div class="p-3 border-t">
        <div class="flex items-center gap-3 px-3 py-2 text-sm text-muted-foreground">
          <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-bold text-primary shrink-0">
            {{ user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-foreground font-medium text-xs truncate">{{ user?.name }}</p>
            <p class="text-xs truncate">{{ user?.email }}</p>
          </div>
          <Link href="/logout" method="post" as="button">
            <LogOut class="w-4 h-4 hover:text-destructive transition-colors" />
          </Link>
        </div>
      </div>
    </aside>

    <main class="flex-1 overflow-auto">
      <!-- Flash message -->
      <div
        v-if="$page.props.flash?.success"
        class="mx-8 mt-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800"
      >
        {{ $page.props.flash.success }}
      </div>
      <div class="p-8">
        <slot />
      </div>
    </main>
  </div>
</template>
