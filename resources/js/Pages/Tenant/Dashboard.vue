<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import { MessageSquare, AlertTriangle, Activity, QrCode, Play, ExternalLink, Loader2 } from 'lucide-vue-next';
import { useConnectionStore } from '@/stores/useConnectionStore';

const props = defineProps({
  client: Object,
  stats: Object,
  wa: Object,
});

const connection = useConnectionStore();

onMounted(() => {
  connection.init({ wa: props.wa });
  connection.startPolling(5000);
});
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
            <Badge :variant="connection.statusVariant">{{ connection.statusLabel }}</Badge>
          </div>
        </CardHeader>
        <CardContent>
          <!-- Working / Online -->
          <div v-if="connection.isOnline">
            <p class="text-3xl font-bold text-green-600">Online</p>
            <p class="text-xs text-muted-foreground mt-1 truncate">{{ connection.statusDescription }}</p>
          </div>

          <!-- QR Scan needed -->
          <div v-else-if="connection.isScanning" class="space-y-2">
            <p class="text-xl font-bold text-amber-600">Needs QR Scan</p>
            <p class="text-xs text-muted-foreground">Scan QR to connect WhatsApp</p>
            <Link href="/connections" class="inline-flex items-center gap-1.5 text-xs text-primary font-medium hover:underline pt-1">
              <QrCode class="w-3.5 h-3.5" /> Scan QR in Connections <ExternalLink class="w-3 h-3" />
            </Link>
          </div>

          <!-- Stopped -->
          <div v-else-if="connection.isStopped" class="space-y-2">
            <p class="text-xl font-bold text-muted-foreground">Stopped</p>
            <p class="text-xs text-muted-foreground">WhatsApp session is paused</p>
            <Button
              size="sm"
              variant="outline"
              class="h-7 text-xs gap-1 mt-1"
              :disabled="connection.isStarting"
              @click="connection.startSession"
            >
              <Loader2 v-if="connection.isStarting" class="w-3 h-3 animate-spin" />
              <Play v-else class="w-3 h-3" /> Start Session
            </Button>
          </div>

          <!-- Auth Error / Key issue -->
          <div v-else-if="connection.isAuthError">
            <p class="text-xl font-bold text-destructive">Auth Error</p>
            <p class="text-xs text-destructive/80 mt-1 truncate">Check WAHA API Key permissions</p>
          </div>

          <!-- Other Error -->
          <div v-else-if="connection.waStatus === 'ERROR'">
            <p class="text-xl font-bold text-destructive">Offline</p>
            <p class="text-xs text-muted-foreground mt-1 truncate">{{ connection.statusDescription }}</p>
          </div>

          <!-- Not Configured -->
          <div v-else-if="connection.waStatus === 'NOT_CONFIGURED'">
            <p class="text-xl font-bold text-muted-foreground">Not Configured</p>
            <p class="text-xs text-muted-foreground mt-1">Set WAHA session in admin</p>
          </div>

          <!-- Loading -->
          <div v-else>
            <div class="flex items-center gap-2 text-muted-foreground py-1">
              <Loader2 class="w-4 h-4 animate-spin" />
              <span class="text-sm">Checking status…</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </TenantLayout>
</template>
