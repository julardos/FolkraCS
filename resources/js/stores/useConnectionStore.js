import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useConnectionStore = defineStore('connection', () => {
  // ── State ──────────────────────────────────────────────────
  const waStatus     = ref('LOADING'); // LOADING | NOT_CONFIGURED | STOPPED | STARTING | SCAN_QR_CODE | WORKING | AUTH_ERROR | ERROR
  const waSession    = ref(null);
  const waConfigured = ref(false);
  const waError      = ref(null);
  const waMe         = ref(null); // phone info when connected
  const qrData       = ref(null); // base64 data URL
  const qrLoading    = ref(false);
  const isStarting   = ref(false);

  const instagram    = ref({
    connected: false,
    username: null,
    accountId: null,
    expiresAt: null,
  });
  const channels     = ref('whatsapp');

  let statusPoller = null;
  let qrTimer      = null;

  // ── Getters ────────────────────────────────────────────────
  const isOnline = computed(() => waStatus.value === 'WORKING');
  const isScanning = computed(() => waStatus.value === 'SCAN_QR_CODE');
  const isStopped = computed(() => waStatus.value === 'STOPPED');
  const isAuthError = computed(() => waStatus.value === 'AUTH_ERROR');

  const statusLabel = computed(() => {
    switch (waStatus.value) {
      case 'WORKING':        return 'Online';
      case 'SCAN_QR_CODE':   return 'Waiting for QR Scan';
      case 'STARTING':       return 'Starting…';
      case 'STOPPED':        return 'Stopped';
      case 'NOT_CONFIGURED': return 'Not Configured';
      case 'AUTH_ERROR':     return 'Auth Error';
      case 'ERROR':          return 'Connection Error';
      case 'LOADING':        return 'Checking…';
      default:               return waStatus.value;
    }
  });

  const statusVariant = computed(() => {
    switch (waStatus.value) {
      case 'WORKING':        return 'success';
      case 'SCAN_QR_CODE':   return 'warning';
      case 'STARTING':       return 'outline';
      case 'STOPPED':        return 'secondary';
      case 'NOT_CONFIGURED': return 'secondary';
      case 'AUTH_ERROR':     return 'destructive';
      case 'ERROR':          return 'destructive';
      default:               return 'outline';
    }
  });

  const statusDescription = computed(() => {
    switch (waStatus.value) {
      case 'WORKING':
        return `Receiving & replying on session ${waSession.value ?? 'active'}`;
      case 'SCAN_QR_CODE':
        return 'Scan QR code in WhatsApp to link bot';
      case 'STARTING':
        return 'Starting WAHA WhatsApp session…';
      case 'STOPPED':
        return 'Session is stopped. Click to start.';
      case 'NOT_CONFIGURED':
        return 'WhatsApp session not configured yet.';
      case 'AUTH_ERROR':
        return waError.value || 'Invalid WAHA API key or session permission.';
      case 'ERROR':
        return waError.value || 'Cannot reach WAHA server.';
      default:
        return 'Checking WhatsApp status…';
    }
  });

  // ── Helpers ────────────────────────────────────────────────
  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
  }

  // ── Actions ────────────────────────────────────────────────
  function init(props = {}) {
    if (props.wa) {
      waSession.value    = props.wa.session ?? waSession.value;
      waConfigured.value = props.wa.configured ?? (Boolean(props.wa.session));
    }
    if (props.instagram) {
      instagram.value = { ...instagram.value, ...props.instagram };
    }
    if (props.channels) {
      channels.value = props.channels;
    }

    if (!waConfigured.value) {
      waStatus.value = 'NOT_CONFIGURED';
    } else if (waStatus.value === 'LOADING') {
      fetchWaStatus();
    }
  }

  async function fetchWaStatus() {
    if (!waConfigured.value && waSession.value == null) {
      waStatus.value = 'NOT_CONFIGURED';
      return;
    }

    try {
      const res = await fetch('/connections/wa/status', {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();

      waStatus.value = json.status ?? 'ERROR';
      if (json.session) waSession.value = json.session;
      if (json.me) waMe.value = json.me;
      if (json.message) waError.value = json.message;

      if (waStatus.value === 'WORKING') {
        qrData.value = null;
        waError.value = null;
        if (qrTimer) {
          clearInterval(qrTimer);
          qrTimer = null;
        }
      } else if (waStatus.value === 'SCAN_QR_CODE') {
        if (!qrData.value) {
          await fetchQr();
        }
        if (!qrTimer) {
          qrTimer = setInterval(fetchQr, 20000);
        }
      } else if (waStatus.value === 'STOPPED') {
        qrData.value = null;
      }
    } catch (e) {
      waStatus.value = 'ERROR';
      waError.value = e.message || 'Network error fetching WhatsApp status';
    }
  }

  async function fetchQr() {
    if (qrLoading.value) return;
    qrLoading.value = true;
    try {
      const res = await fetch('/connections/wa/qr', {
        headers: { 'Accept': 'application/json' }
      });
      const json = await res.json();
      if (json.data) {
        qrData.value = `data:${json.mime ?? 'image/png'};base64,${json.data}`;
      }
    } catch (e) {
      // keep previous QR
    } finally {
      qrLoading.value = false;
    }
  }

  async function startSession() {
    isStarting.value = true;
    waStatus.value = 'STARTING';
    waError.value = null;

    try {
      const res = await fetch('/connections/wa/start', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'Accept': 'application/json'
        }
      });
      const json = await res.json();
      if (json.status) {
        waStatus.value = json.status;
      }
      setTimeout(fetchWaStatus, 1500);
    } catch (e) {
      waStatus.value = 'ERROR';
      waError.value = e.message || 'Failed to start session';
    } finally {
      isStarting.value = false;
    }
  }

  function startPolling(intervalMs = 6000) {
    if (!statusPoller) {
      fetchWaStatus();
      statusPoller = setInterval(fetchWaStatus, intervalMs);
    }
  }

  function stopPolling() {
    if (statusPoller) {
      clearInterval(statusPoller);
      statusPoller = null;
    }
    if (qrTimer) {
      clearInterval(qrTimer);
      qrTimer = null;
    }
  }

  return {
    // state
    waStatus,
    waSession,
    waConfigured,
    waError,
    waMe,
    qrData,
    qrLoading,
    isStarting,
    instagram,
    channels,
    // getters
    isOnline,
    isScanning,
    isStopped,
    isAuthError,
    statusLabel,
    statusVariant,
    statusDescription,
    // actions
    init,
    fetchWaStatus,
    fetchQr,
    startSession,
    startPolling,
    stopPolling,
  };
});
