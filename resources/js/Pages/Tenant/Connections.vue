<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardFooter from '@/components/ui/card/CardFooter.vue';
import Button from '@/components/ui/button/Button.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Wifi, Instagram, RefreshCw, CheckCircle2, XCircle, Loader2, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
  channels:  String,
  wa:        Object,
  instagram: Object,
});

// ── WhatsApp state ───────────────────────────────────────────
const waStatus  = ref('LOADING');  // LOADING | NOT_CONFIGURED | STOPPED | STARTING | SCAN_QR_CODE | WORKING | ERROR
const qrData    = ref(null);       // base64 PNG
const waError   = ref(null);
const qrLoading = ref(false);

let statusPoller = null;
let qrTimer      = null;

async function fetchWaStatus() {
  try {
    const res = await fetch('/connections/wa/status');
    const json = await res.json();
    waStatus.value = json.status ?? 'ERROR';

    if (waStatus.value === 'SCAN_QR_CODE') {
      await fetchQr();
    } else if (waStatus.value === 'WORKING') {
      qrData.value = null;
      clearInterval(qrTimer);
    }
  } catch {
    waStatus.value = 'ERROR';
  }
}

async function fetchQr() {
  qrLoading.value = true;
  try {
    const res  = await fetch('/connections/wa/qr');
    const json = await res.json();
    if (json.data) qrData.value = `data:${json.mime ?? 'image/png'};base64,${json.data}`;
  } catch {
    // QR temporarily unavailable — keep old one
  } finally {
    qrLoading.value = false;
  }
}

async function startSession() {
  waError.value  = null;
  waStatus.value = 'STARTING';
  try {
    await fetch('/connections/wa/start', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken() } });
  } catch (e) {
    waError.value = e.message;
  }
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

const waStatusLabel = computed(() => ({
  LOADING:       'Checking…',
  NOT_CONFIGURED:'Not configured',
  STOPPED:       'Stopped',
  STARTING:      'Starting…',
  SCAN_QR_CODE:  'Waiting for QR scan',
  WORKING:       'Connected',
  ERROR:         'Error',
}[waStatus.value] ?? waStatus.value));

const waStatusVariant = computed(() => ({
  WORKING:       'success',
  SCAN_QR_CODE:  'warning',
  STOPPED:       'secondary',
  NOT_CONFIGURED:'secondary',
  ERROR:         'destructive',
}[waStatus.value] ?? 'outline'));

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
  if (props.wa.configured) {
    fetchWaStatus();
    statusPoller = setInterval(fetchWaStatus, 5000);
    qrTimer      = setInterval(fetchQr, 30000);
  } else {
    waStatus.value = 'NOT_CONFIGURED';
  }
});

onUnmounted(() => {
  clearInterval(statusPoller);
  clearInterval(qrTimer);
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
                <CardDescription>Scan QR to authenticate your WhatsApp session ({{ wa.session ?? '—' }})</CardDescription>
              </div>
            </div>
            <Badge :variant="waStatusVariant">{{ waStatusLabel }}</Badge>
          </div>
        </CardHeader>

        <CardContent>
          <!-- Not configured by landlord yet -->
          <div v-if="waStatus === 'NOT_CONFIGURED'" class="flex items-center gap-2 text-sm text-amber-600 bg-amber-50 rounded-md p-3">
            <AlertTriangle class="w-4 h-4 shrink-0" />
            WhatsApp session not configured yet. Contact your admin to set up the WAHA session.
          </div>

          <!-- Connected -->
          <div v-else-if="waStatus === 'WORKING'" class="flex items-center gap-2 text-sm text-green-700 bg-green-50 rounded-md p-4">
            <CheckCircle2 class="w-5 h-5 shrink-0" />
            <div>
              <p class="font-medium">WhatsApp is connected and active.</p>
              <p class="text-xs text-green-600 mt-0.5">The AI is receiving and replying to messages on session <strong>{{ wa.session }}</strong>.</p>
            </div>
          </div>

          <!-- QR scan needed -->
          <div v-else-if="waStatus === 'SCAN_QR_CODE'" class="space-y-4">
            <p class="text-sm text-muted-foreground">Open WhatsApp on your phone → Linked Devices → Link a Device, then scan this QR code.</p>
            <div class="flex items-start gap-6">
              <div class="border rounded-xl p-3 bg-white inline-block">
                <div v-if="qrLoading && !qrData" class="w-48 h-48 flex items-center justify-center">
                  <Loader2 class="w-8 h-8 animate-spin text-muted-foreground" />
                </div>
                <img v-else-if="qrData" :src="qrData" alt="WhatsApp QR Code" class="w-48 h-48 object-contain" />
                <div v-else class="w-48 h-48 flex items-center justify-center text-xs text-muted-foreground">No QR available</div>
              </div>
              <div class="text-sm space-y-2 text-muted-foreground pt-2">
                <p>QR refreshes automatically every 30 seconds.</p>
                <p>Once scanned, this page updates instantly.</p>
                <Button variant="outline" size="sm" @click="fetchQr" class="gap-1">
                  <RefreshCw class="w-3.5 h-3.5" /> Refresh QR
                </Button>
              </div>
            </div>
          </div>

          <!-- Stopped / needs start -->
          <div v-else-if="waStatus === 'STOPPED' || waStatus === 'ERROR'" class="space-y-3">
            <div class="flex items-center gap-2 text-sm text-muted-foreground bg-muted rounded-md p-3">
              <XCircle class="w-4 h-4 shrink-0 text-destructive" />
              Session is stopped. Start it to show the QR code.
            </div>
          </div>

          <!-- Loading -->
          <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
            <Loader2 class="w-4 h-4 animate-spin" /> Checking session status…
          </div>
        </CardContent>

        <CardFooter v-if="waStatus === 'STOPPED' || waStatus === 'ERROR'">
          <Button @click="startSession" :disabled="waStatus === 'STARTING'" class="gap-2">
            <Loader2 v-if="waStatus === 'STARTING'" class="w-4 h-4 animate-spin" />
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
