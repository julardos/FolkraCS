<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import {
  Users, MessageSquare, AlertTriangle, Activity,
  ExternalLink, Bot, TrendingUp
} from 'lucide-vue-next';

const props = defineProps({ stats: Object, clients: Array });

const statusVariant = (s) => ({ active: 'success', suspended: 'destructive', inactive: 'secondary' }[s] ?? 'outline');
</script>

<template>
  <AppLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
      <p class="text-muted-foreground text-sm mt-1">FolkraCS platform overview.</p>
    </div>

    <!-- Stats row -->
    <div class="grid grid-cols-4 gap-4 mb-8">
      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Active Clients</CardTitle>
            <Users class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ stats.active_clients }}</p>
          <p class="text-xs text-muted-foreground mt-1">of {{ stats.total_clients }} total</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Conversations</CardTitle>
            <MessageSquare class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ stats.total_conversations }}</p>
          <p class="text-xs text-muted-foreground mt-1">{{ stats.active_conversations }} active</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Open Tickets</CardTitle>
            <AlertTriangle class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ stats.open_tickets }}</p>
          <p class="text-xs text-muted-foreground mt-1">across all clients</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Total Customers</CardTitle>
            <TrendingUp class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ stats.total_customers }}</p>
          <p class="text-xs text-muted-foreground mt-1">{{ stats.total_users }} dashboard users</p>
        </CardContent>
      </Card>
    </div>

    <!-- Client list -->
    <div class="mb-4 flex items-center justify-between">
      <h2 class="text-base font-semibold">Clients</h2>
      <Link href="/clients">
        <span class="text-sm text-primary hover:underline">Manage clients →</span>
      </Link>
    </div>

    <div v-if="!clients.length" class="text-center py-16 text-muted-foreground">
      <Bot class="w-10 h-10 mx-auto mb-3 opacity-40" />
      <p>No clients yet. <Link href="/clients" class="text-primary underline">Add your first client.</Link></p>
    </div>

    <div class="space-y-2">
      <Card
        v-for="client in clients"
        :key="client.id"
        class="hover:shadow-sm transition-shadow"
      >
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
              <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center font-bold text-primary text-sm shrink-0">
                {{ client.name.charAt(0) }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <span class="font-medium text-sm">{{ client.name }}</span>
                  <Badge :variant="statusVariant(client.status)" class="text-xs">{{ client.status }}</Badge>
                </div>
                <p class="text-xs text-muted-foreground">{{ client.business_type ?? '—' }}</p>
              </div>
            </div>

            <div class="flex items-center gap-6 text-sm">
              <div class="text-center">
                <p class="font-semibold">{{ client.conversations }}</p>
                <p class="text-xs text-muted-foreground">conversations</p>
              </div>
              <div class="text-center">
                <p class="font-semibold" :class="client.open_tickets > 0 ? 'text-amber-600' : ''">{{ client.open_tickets }}</p>
                <p class="text-xs text-muted-foreground">open tickets</p>
              </div>
              <a
                v-if="client.domain"
                :href="`https://${client.domain}/dashboard`"
                target="_blank"
                class="flex items-center gap-1 text-xs text-primary hover:underline"
              >
                Dashboard <ExternalLink class="w-3 h-3" />
              </a>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
