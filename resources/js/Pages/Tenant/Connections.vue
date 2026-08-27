<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Wifi, Instagram, RefreshCw, CheckCircle2, XCircle, Loader2, AlertTriangle, ShieldAlert, RotateCw } from 'lucide-vue-next';
import { useConnectionStore } from '@/stores/useConnectionStore';

const props = defineProps({
  channels:  String,
  wa:        Object,
  instagram: Object,
});

const connection = useConnectionStore();

function connectInstagram() {
  window.location.href = '/connections/instagram';
}

function disconnectInstagram() {
  if (confirm('Disconnect Instagram? The AI will stop receiving Instagram messages.')) {
    router.delete('/connections/instagram');
  }
}

onMounted(() => {
  connection.init({
    wa:        props.wa,
    instagram: props.instagram,
    channels:  props.channels,
  });
  connection.startPolling(5000);
});

onUnmounted(() => connection.stopPolling());
</script>

<template>
  <TenantLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight">Connections</h1>
      <p class="text-muted-foreground text-sm mt-1">Connect your messaging channels so the AI can receive and reply to messages.</p>
    </div>

    <div class="space-y-6">

      <!-- ── WhatsApp ──────────────────────────────────────────────── -->
      <Card>
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
                <Wifi class="w-5 h-5 text-green-600" />
              </div>
              <div>
                <CardTitle>WhatsApp</CardTitle>
                <CardDescription>Session: {{ connection.waSession ?? wa?.session ?? '—' }}</CardDescription>
              </div>
            </div>
            <Badge :variant="connection.statusVariant">{{ connection.statusLabel }}</Badge>
          </div>
        </CardHeader>

        <CardContent>

          <!-- Not configured -->
          <div v-if="connection.waStatus === 'NOT_CONFIGURED'"
               class="flex items-start gap-3 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-4">
            <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
            <div>
              <p class="font-medium">WhatsApp not set up yet</p>
              <p class="text-xs mt-1 text-amber-600">Your WhatsApp session hasn't been configured. Please contact support to get this activated.</p>
            </div>
          </div>

          <!-- Connected -->
          <div v-else-if="connection.isOnline"
               class="flex items-center gap-3 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md p-4">
            <CheckCircle2 class="w-5 h-5 shrink-0" />
            <div>
              <p class="font-medium">WhatsApp is connected and active</p>
              <p class="text-xs text-green-600 mt-0.5">Your AI is receiving and replying to messages automatically.</p>
            </div>
          </div>

          <!-- QR scan -->
          <div v-else-if="connection.isScanning" class="space-y-4">
            <p class="text-sm text-muted-foreground">
              Open WhatsApp on your phone → tap <strong>Linked Devices</strong> → <strong>Link a Device</strong>, then scan this code.
            </p>
            <div class="flex items-start gap-6">
              <div class="border rounded-xl p-3 bg-white inline-block">
                <div v-if="connection.qrLoading && !connection.qrData" class="w-48 h-48 flex items-center justify-center">
                  <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
                <img v-else-if="connection.qrData" :src="connection.qrData" alt="WhatsApp QR Code" class="w-48 h-48 object-contain" />
                <div v-else class="w-48 h-48 flex items-center justify-center text-xs text-muted-foreground">QR not available</div>
              </div>
              <div class="text-sm space-y-3 text-muted-foreground pt-2">
                <p>The QR code refreshes automatically every 20 seconds.</p>
                <p>Once scanned, this page will update instantly.</p>
                <Button variant="outline" size="sm" @click="connection.fetchQr" :disabled="connection.qrLoading" class="gap-1.5">
                  <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': connection.qrLoading }" />
                  Refresh QR
                </Button>
              </div>
            </div>
          </div>

          <!-- Auth error — wrong API key, client can't fix this -->
          <div v-else-if="connection.isAuthError"
               class="flex items-start gap-3 text-sm bg-destructive/5 border border-destructive/20 rounded-md p-4">
            <ShieldAlert class="w-5 h-5 shrink-0 mt-0.5 text-destructive" />
            <div>
              <p class="font-medium text-destructive">WhatsApp access denied</p>
              <p class="text-xs text-muted-foreground mt-1">
                There's a configuration issue on our end. Please contact support and we'll fix it for you.
              </p>
            </div>
          </div>

          <!-- Starting -->
          <div v-else-if="connection.waStatus === 'STARTING'"
               class="flex items-center gap-3 text-sm text-muted-foreground bg-muted border border-border rounded-md p-4">
            <Loader2 class="w-4 h-4 animate-spin shrink-0" />
            <div>
              <p class="font-medium">Memulai sesi WhatsApp…</p>
              <p class="text-xs mt-0.5">Harap tunggu, QR code akan muncul sebentar lagi.</p>
            </div>
          </div>

          <!-- Stopped -->
          <div v-else-if="connection.waStatus === 'STOPPED'"
               class="flex items-center gap-3 text-sm text-muted-foreground bg-muted border border-border rounded-md p-4">
            <XCircle class="w-4 h-4 shrink-0" />
            <div>
              <p class="font-medium">Sesi WhatsApp tidak aktif</p>
              <p class="text-xs mt-0.5">Klik <strong>Mulai Sesi</strong> di bawah — QR code akan muncul untuk di-scan.</p>
            </div>
          </div>

          <!-- Failed / Error — can restart -->
          <div v-else-if="connection.waStatus === 'FAILED' || connection.waStatus === 'ERROR'"
               class="flex items-start gap-3 text-sm bg-destructive/5 border border-destructive/20 rounded-md p-4">
            <AlertTriangle class="w-5 h-5 shrink-0 mt-0.5 text-destructive" />
            <div>
              <p class="font-medium text-destructive">
                {{ connection.waStatus === 'FAILED' ? 'Sesi WhatsApp gagal' : 'WhatsApp tidak dapat dijangkau' }}
              </p>
              <p class="text-xs text-muted-foreground mt-1">
                Klik <strong>Restart Sesi</strong> di bawah untuk mencoba menghubungkan kembali.
              </p>
            </div>
          </div>

          <!-- Loading / unknown -->
          <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
            <Loader2 class="w-4 h-4 animate-spin" /> Memeriksa koneksi…
          </div>

        </CardContent>

        <CardFooter v-if="['STOPPED','FAILED','ERROR'].includes(connection.waStatus)" class="gap-2">
          <Button v-if="connection.waStatus === 'STOPPED'"
                  @click="connection.startSession" :disabled="connection.isStarting" class="gap-2">
            <Loader2 v-if="connection.isStarting" class="w-4 h-4 animate-spin" />
            {{ connection.isStarting ? 'Memulai…' : 'Mulai Sesi' }}
          </Button>
          <Button v-if="connection.waStatus === 'FAILED' || connection.waStatus === 'ERROR'"
                  @click="connection.restartSession" :disabled="connection.isRestarting" class="gap-2">
            <Loader2 v-if="connection.isRestarting" class="w-4 h-4 animate-spin" />
            <RotateCw v-else class="w-4 h-4" />
            {{ connection.isRestarting ? 'Memulai ulang…' : 'Restart Sesi' }}
          </Button>
        </CardFooter>
      </Card>

      <!-- ── Instagram ─────────────────────────────────────────────── -->
      <Card v-if="channels === 'whatsapp_instagram'">
        <CardHeader>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center">
                <Instagram class="w-5 h-5 text-pink-600" />
              </div>
              <div>
                <CardTitle>Instagram</CardTitle>
                <CardDescription>Receive and reply to Instagram DMs automatically</CardDescription>
              </div>
            </div>
            <Badge :variant="connection.instagram.connected ? 'success' : 'secondary'">
              {{ connection.instagram.connected ? 'Connected' : 'Not connected' }}
            </Badge>
          </div>
        </CardHeader>

        <CardContent>
          <!-- Connected -->
          <div v-if="connection.instagram.connected"
               class="flex items-center justify-between bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex items-center gap-3">
              <CheckCircle2 class="w-5 h-5 text-green-600 shrink-0" />
              <div>
                <p class="text-sm font-medium text-green-800">
                  @{{ connection.instagram.username ?? connection.instagram.accountId }}
                </p>
                <p class="text-xs text-green-600 mt-0.5">
                  {{ connection.instagram.expiresAt ? `Token expires ${connection.instagram.expiresAt}` : 'Token does not expire' }}
                </p>
              </div>
            </div>
            <Button variant="outline" size="sm" @click="disconnectInstagram">Disconnect</Button>
          </div>

          <!-- Not connected -->
          <div v-else class="space-y-3">
            <p class="text-sm text-muted-foreground">
              Connect your Instagram Business account so the AI can receive and reply to DMs.
              Make sure your Instagram is set up as a <strong>Business or Creator</strong> account linked to a Facebook Page.
            </p>
          </div>
        </CardContent>

        <CardFooter v-if="!connection.instagram.connected">
          <Button @click="connectInstagram" class="gap-2">
            <Instagram class="w-4 h-4" /> Connect Instagram
          </Button>
        </CardFooter>
      </Card>

      <!-- Instagram not in plan -->
      <Card v-else class="opacity-60 pointer-events-none">
        <CardHeader>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-pink-50 flex items-center justify-center">
              <Instagram class="w-5 h-5 text-pink-400" />
            </div>
            <div>
              <CardTitle class="text-muted-foreground">Instagram</CardTitle>
              <CardDescription>Not included in your current plan.</CardDescription>
            </div>
          </div>
        </CardHeader>
      </Card>

    </div>
  </TenantLayout>
</template>
