<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutDashboard, Users, MessageSquare, Bot, LogOut, Settings } from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth.user);

const nav = [
  { label: 'Dashboard',     href: '/dashboard',     icon: LayoutDashboard },
  { label: 'Clients',       href: '/clients',        icon: Users },
  { label: 'Conversations', href: '/conversations',  icon: MessageSquare },
  { label: 'Settings',      href: '/settings',       icon: Settings },
];
</script>

<template>
  <div class="flex h-screen bg-background">
    <!-- Sidebar -->
    <aside class="w-60 border-r bg-card flex flex-col">
      <div class="p-6 border-b">
        <div class="flex items-center gap-2">
          <Bot class="w-6 h-6 text-primary" />
          <span class="font-bold text-lg tracking-tight">FolkraCS</span>
        </div>
        <p class="text-xs text-muted-foreground mt-1">AI WhatsApp CS Platform</p>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <Link
          v-for="item in nav"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors"
          :class="(item.href === '/dashboard' ? $page.url === item.href : $page.url.startsWith(item.href))
            ? 'bg-primary text-primary-foreground'
            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
        >
          <component :is="item.icon" class="w-4 h-4" />
          {{ item.label }}
        </Link>
      </nav>

      <div class="p-3 border-t">
        <div class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-muted-foreground">
          <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary">
            {{ user?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-medium text-foreground truncate text-xs">{{ user?.name }}</p>
            <p class="text-xs truncate">{{ user?.email }}</p>
          </div>
          <Link href="/logout" method="post" as="button">
            <LogOut class="w-4 h-4 hover:text-destructive transition-colors" />
          </Link>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-auto">
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
