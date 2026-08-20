<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { MessageSquare, AlertTriangle, Activity } from 'lucide-vue-next';

const props = defineProps({ client: Object, stats: Object });
</script>

<template>
  <TenantLayout>
    <div class="mb-8">
      <div class="flex items-center gap-3">
        <h1 class="text-2xl font-bold tracking-tight">Dashboard</h1>
        <Badge :variant="client.status === 'active' ? 'success' : 'secondary'">{{ client.status }}</Badge>
      </div>
      <p class="text-muted-foreground text-sm mt-1">{{ client.name }} — AI CS Overview</p>
    </div>

    <div class="grid grid-cols-3 gap-4">
      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium text-muted-foreground">Total Conversations</CardTitle>
            <MessageSquare class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ stats.conversations }}</p>
          <p class="text-xs text-muted-foreground mt-1">{{ stats.active }} active now</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium text-muted-foreground">Open Tickets</CardTitle>
            <AlertTriangle class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold">{{ stats.open_tickets }}</p>
          <p class="text-xs text-muted-foreground mt-1">Awaiting agent response</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="pb-2">
          <div class="flex items-center justify-between">
            <CardTitle class="text-sm font-medium text-muted-foreground">Bot Status</CardTitle>
            <Activity class="w-4 h-4 text-muted-foreground" />
          </div>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-bold text-green-600">Online</p>
          <p class="text-xs text-muted-foreground mt-1">Receiving messages</p>
        </CardContent>
      </Card>
    </div>
  </TenantLayout>
</template>
