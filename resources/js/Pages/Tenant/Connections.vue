<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Wifi, Instagram, RefreshCw, CheckCircle2, XCircle, Loader2, AlertTriangle, ShieldAlert } from 'lucide-vue-next';
import { useConnectionStore } from '@/stores/useConnectionStore';

const props = defineProps({
  channels:  String,
  wa:        Object,
  instagram: Object,
});

const connection = useConnectionStore();

// ── Instagram state ──────────────────────────────────────────
function connectInstagram() {
  window.location.href = '/connections/instagram';
}

function disconnectInstagram() {
  if (confirm('Disconnect Instagram? The AI will stop receiving Instagram messages.')) {
    router.delete('/connections/instagram');
  }
}

// ── Lifecycle ────────────────────────────────────────────────
onMounted(() => {
  connection.init({
    wa: props.wa,
    instagram: props.instagram,
    channels: props.channels,
  });
  connection.startPolling(5000);
});
</script>

<template>
  <TenantLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">Connections</h1>
      <p class="text-muted-foreground text-sm mt-1">Connect your messaging channels so the AI can receive and reply to messages.</p>
    </div>

    <div class="space-y-6">

      <!-- ── WhatsApp ──────────────────────────────────────── -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                <Wifi class="w-5 h-5 text-green-600" />
              </div>
              <div>
                <CardTitle>WhatsApp</CardTitle>
                <CardDescription>Scan QR to authenticate your WhatsApp session ({{ connection.waSession ?? wa?.session ?? '—' }})</CardDescription>
              </div>
            </div>
            <Badge :variant="connection.statusVariant">{{ connection.statusLabel }}</Badge>
          </div>
        </CardHeader>

        <CardContent>
          <!-- Not configured by landlord yet -->
          <div v-if="connection.waStatus === 'NOT_CONFIGURED'" class="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 rounded-md p-3">
            <AlertTriangle class="w-4 h-4 shrink-0" />
            WhatsApp session not configured yet. Contact your admin to set up the WAHA session.
          </div>

          <!-- Connected -->
          <div v-else-if="connection.isOnline" class="flex items-center gap-2 text-sm text-green-700 bg-green-50 rounded-md p-4">
            <CheckCircle2 class="w-5 h-5 shrink-0" />
            <div>
              <p class="font-medium">WhatsApp is connected and active.</p>
              <p class="text-xs text-green-600 mt-0.5">The AI is receiving and replying to messages on session <strong>{{ connection.waSession }}</strong>.</p>
            </div>
          </div>

          <!-- QR scan needed -->
          <div v-else-if="connection.isScanning" class="space-y-4">
            <p class="text-sm text-muted-foreground">Open WhatsApp on your phone → Linked Devices → Link a Device, then scan this QR code.</p>
            <div class="flex items-start gap-6">
              <div class="border rounded-xl p-3 bg-white inline-block">
                <div v-if="connection.qrLoading && !connection.qrData" class="w-48 h-48 flex items-center justify-center">
                  <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
                <img v-else-if="connection.qrData" :src="connection.qrData" alt="WhatsApp QR Code" class="w-48 h-48 object-contain" />
                <div v-else class="w-48 h-48 flex items-center justify-center text-xs text-muted-foreground">No QR available</div>
              </div>
              <div class="text-sm space-y-2 text-muted-foreground pt-2">
                <p>QR refreshes automatically every 20 seconds.</p>
                <p>Once scanned, this page updates instantly.</p>
                <Button variant="outline" size="sm" @click="connection.fetchQr" :disabled="connection.qrLoading" class="gap-1">
                  <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': connection.qrLoading }" /> Refresh QR
                </Button>
              </div>
            </div>
          </div>

          <!-- Auth Error (API Key permissions mismatch) -->
          <div v-else-if="connection.isAuthError" class="space-y-3">
            <div class="flex items-start gap-3 text-sm text-destructive bg-destructive/10 rounded-md p-3">
              <ShieldAlert class="w-5 h-5 shrink-0 mt-0.5" />
              <div>
                <p class="font-medium">Authentication Error (403 Forbidden)</p>
                <p class="text-xs mt-1">WAHA rejected access to session <strong>{{ connection.waSession }}</strong>. Ensure your WAHA API key has permission for this session.</p>
              </div>
            </div>
          </div>

          <!-- Stopped session -->
          <div v-else-if="connection.isStopped" class="space-y-3">
            <div class="flex items-center gap-2 text-sm text-muted-foreground bg-muted rounded-md p-3">
              <XCircle class="w-4 h-4 shrink-0 text-muted-foreground" />
              Session is stopped. Start it to show the QR code.
            </div>
          </div>

          <!-- Connection Error -->
          <div v-else-if="connection.waStatus === 'ERROR'" class="space-y-3">
            <div class="flex items-start gap-3 text-sm text-destructive bg-destructive/10 rounded-md p-3">
              <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5" />
              <div>
                <p class="font-medium">WAHA Connection Error</p>
                <p class="text-xs mt-1">{{ connection.waError || 'Failed to connect to WAHA server.' }}</p>
              </div>
            </div>
          </div>

          <!-- Loading -->
          <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
            <Loader2 class="w-4 h-4 animate-spin" /> Checking session status…
          </div>
        </CardContent>

        <CardFooter v-if="connection.isStopped || connection.waStatus === 'ERROR'">
          <Button @click="connection.startSession" :disabled="connection.isStarting" class="gap-2">
            <Loader2 v-if="connection.isStarting" class="w-4 h-4 animate-spin" />
            Start Session
          </Button>
        </CardFooter>
      </Card>

      <!-- ── Instagram ─────────────────────────────────────── -->
      <Card v-if="channels === 'whatsapp_instagram'">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                <Instagram class="w-5 h-5 text-pink-600" />
              </div>
              <div>
                <CardTitle>Instagram</CardTitle>
                <CardDescription>Connect your Instagram Business account to receive DMs</CardDescription>
              </div>
            </div>
            <Badge :variant="instagram.connected ? 'success' : 'secondary'">
              {{ instagram.connected ? 'Connected' : 'Not connected' }}
            </Badge>
          </div>
        </CardHeader>

        <CardContent>
          <!-- Connected -->
          <div v-if="instagram.connected" class="flex items-center justify-between bg-green-50 rounded-md p-4">
            <div class="flex items-center gap-3">
              <CheckCircle2 class="w-5 h-5 text-green-600 shrink-0" />
              <div>
                <p class="text-sm font-medium text-green-800">@{{ instagram.username ?? instagram.accountId }}</p>
                <p class="text-xs text-green-600 mt-0.5">Token expires {{ instagram.expiresAt ?? 'unknown' }}</p>
              </div>
            </div>
            <Button variant="outline" size="sm" @click="disconnectInstagram">Disconnect</Button>
          </div>

          <!-- Not connected -->
          <div v-else class="space-y-3">
            <p class="text-sm text-muted-foreground">
              Click below to authorize via Meta. You'll be redirected to Facebook to grant the required permissions.
              Make sure your Instagram account is a <strong>Business or Creator</strong> account connected to a Facebook Page.
            </p>
            <ul class="text-xs text-muted-foreground space-y-1 list-disc ml-4">
              <li>Permissions requested: <code>instagram_manage_messages</code>, <code>instagram_basic</code>, <code>pages_messaging</code></li>
              <li>Your access token is stored securely and never shared.</li>
              <li>Token is valid for 60 days — you'll need to reconnect after that.</li>
            </ul>
          </div>
        </CardContent>

        <CardFooter v-if="!instagram.connected">
          <Button @click="connectInstagram" class="gap-2">
            <Instagram class="w-4 h-4" /> Connect Instagram via Meta
          </Button>
        </CardFooter>
      </Card>

      <!-- Instagram not in plan -->
      <Card v-else class="opacity-60">
        <CardHeader>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-pink-50 flex items-center justify-center">
              <Instagram class="w-5 h-5 text-pink-400" />
            </div>
            <div>
              <CardTitle class="text-muted-foreground">Instagram</CardTitle>
              <CardDescription>Not included in your current plan (WhatsApp only).</CardDescription>
            </div>
          </div>
        </CardHeader>
      </Card>

    </div>
  </TenantLayout>
</template>
