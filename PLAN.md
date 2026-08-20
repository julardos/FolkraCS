# FolkraCS — Implementation Plan

> AI-powered WhatsApp Customer Service SaaS, built natively in Laravel.  
> Replaces the n8n workflow used by Sejukin Indonesia with a fully customizable, multi-tenant platform.

---

## 1. What We're Replacing (n8n Flow)

```
WA Webhook → Check Live Agent → Filter (not-me, not-group, not-takeover)
  → AI Agent (OpenRouter + Memory + RAG tool)
    → Parse Output (%%BOOKING_CONFIRMED%% / %%KENDALA_DETECTED%%)
      → Switch: Booking | Kendala | Default
        → Record to DB → Send WA Reply → Send Invoice (optional)
```

Every step becomes a first-class Laravel component.

---

## 2. Architecture Overview

```
folkra-cs/
├── app/
│   ├── Models/           # Eloquent models
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Middleware/
│   ├── Services/          # Business logic (stateless)
│   ├── Jobs/              # Queued AI processing
│   └── Modules/           # Feature modules (see §4)
│       ├── WhatsApp/
│       ├── AI/
│       ├── Booking/
│       ├── Support/
│       ├── LiveAgent/
│       └── Billing/
├── config/
│   └── folkra.php         # Global defaults
├── database/
│   └── migrations/
└── routes/
    ├── api.php
    └── web.php
```

**Multi-tenancy strategy:** `stancl/tenancy` (database-per-tenant OR shared DB with `tenant_id` column).  
Start with shared DB + `tenant_id` for simplicity; isolate per-DB later if needed.

---

## 3. Database Schema

### Central (Landlord) DB

```sql
tenants
  id, name, slug, config(json)
  status(trial|active|past_due|suspended|cancelled)
  trial_ends_at(timestamp, nullable)
  created_at, updated_at

tenant_wa_sessions id, tenant_id, session_name, base_url, api_key, active
tenant_ai_configs  id, tenant_id, provider(openrouter), model, api_key, system_prompt(text)
tenant_features    id, tenant_id, feature(string), enabled(bool), config(json)
users              id, tenant_id, name, email, password, role

-- BILLING TABLES (landlord DB, not per-tenant) --

plans
  id, name(Starter|Pro|Enterprise), slug
  price(decimal)                    -- monthly price in IDR
  description(text)
  limits(json)                      -- e.g. {"wa_sessions":1,"messages_per_month":5000}
  is_active(bool)
  created_at

subscriptions
  id, tenant_id, plan_id
  status(trial|active|past_due|suspended|cancelled)
  billing_cycle_day(int)            -- day of month invoice is generated, e.g. 1
  current_period_start(date)
  current_period_end(date)
  trial_ends_at(timestamp, nullable)
  cancelled_at(timestamp, nullable)
  created_at, updated_at

invoices
  id, tenant_id, subscription_id
  invoice_number(string, unique)    -- e.g. INV-2026-08-0001
  amount(decimal)
  status(draft|open|paid|void|uncollectible)
  issued_at(date)
  due_at(date)                      -- usually issued_at + 14 days
  paid_at(timestamp, nullable)
  notes(text, nullable)
  created_at, updated_at

payments
  id, tenant_id, invoice_id
  amount(decimal)
  method(bank_transfer|midtrans|xendit|manual)
  reference(string)                 -- bank ref or payment gateway ID
  proof_url(string, nullable)       -- upload bukti transfer
  status(pending|verified|failed)
  paid_at(timestamp)
  verified_by(user_id, nullable)    -- if manually verified by landlord admin
  created_at

billing_contacts
  id, tenant_id
  name, email, phone
  is_primary(bool)
```

### Tenant Data (shared DB, all have `tenant_id`)

```sql
customers
  id, tenant_id, name, phone, phone_lid, wa_session
  is_human_takeover(bool), takeover_agent_id(nullable)
  created_at, updated_at

conversations
  id, tenant_id, customer_id, wa_session
  status(active|closed|escalated)
  created_at, updated_at

messages
  id, tenant_id, conversation_id
  role(user|assistant|system)
  content(text), raw_output(text, nullable)
  created_at

bookings
  id, tenant_id, customer_id, conversation_id
  customer_name, customer_phone, customer_address
  ac_brand, ac_pk(decimal 5,2), is_inverter(bool)
  service_type, qty, ac_problem
  product_id, total_amount(decimal)
  preferred_time(string), preferred_timedate(timestamp, nullable)
  status(pending|confirmed|completed|cancelled)
  invoice_number, invoice_url
  created_at, updated_at

support_tickets
  id, tenant_id, customer_id, conversation_id
  customer_name, customer_phone
  ac_problem(text), kendala_type(complaint|question|escalation|schedule_change)
  status(open|in_progress|resolved)
  assigned_agent_id(nullable)
  created_at, updated_at

knowledge_bases
  id, tenant_id, name, type(text|qa|document)
  content(longtext)
  is_active(bool)

products (tenant service catalog)
  id, tenant_id, product_category_id
  name, description, price(decimal), price_high_pk(decimal), status(bool)

product_categories
  id, tenant_id, name, slug, status
```

---

## 4. Laravel Modules

Using the **nwidart/laravel-modules** package. Each module is an isolated package.

### Module: `WhatsApp`
**Responsibility:** Everything WA API (WAHA-compatible).

```
Modules/WhatsApp/
├── Http/Controllers/WebhookController.php   ← receives WA events
├── Services/WahaClient.php                  ← sendText, sendFile, etc.
├── DTOs/IncomingMessage.php
└── Events/MessageReceived.php
```

Key logic:
- `POST /webhook/{tenant}/{session}` receives raw WAHA payload
- Validates header auth (per-session API key)
- Fires `MessageReceived` event → picked up by AI module
- `WahaClient::sendText(chatId, text, session)` wraps the API call

### Module: `AI`
**Responsibility:** OpenRouter calls, conversation memory, output parsing.

```
Modules/AI/
├── Jobs/ProcessMessageJob.php               ← queued, one per message
├── Services/
│   ├── ConversationMemory.php               ← loads/saves messages from DB
│   ├── OpenRouterClient.php                 ← HTTP to openrouter.ai
│   ├── PromptBuilder.php                    ← builds system prompt from tenant config
│   └── OutputParser.php                     ← parses %%BOOKING_CONFIRMED%% etc.
├── DTOs/
│   ├── ParsedOutput.php
│   ├── BookingData.php
│   └── KendalaData.php
└── Listeners/HandleIncomingMessage.php      ← listens to MessageReceived, dispatches job
```

Key logic:
- `ConversationMemory` loads last N messages from `messages` table (replaces n8n buffer window)
- `PromptBuilder` fetches `system_prompt` from `tenant_ai_configs`, injects current datetime
- `OpenRouterClient` sends `messages[]` array (system + history + new user msg)
- `OutputParser` mirrors the n8n JS code — extracts booking/kendala JSON blocks
- After parse, fires `BookingConfirmed` or `KendalaDetected` events

### Module: `Booking`
**Responsibility:** Create and manage service bookings.

```
Modules/Booking/
├── Listeners/HandleBookingConfirmed.php
├── Services/BookingService.php              ← creates booking, generates invoice
├── Http/Controllers/BookingController.php   ← admin CRUD
└── Models/Booking.php
```

### Module: `Support`
**Responsibility:** Customer support tickets, escalations, and configurable notification routing.

```
Modules/Support/
├── Listeners/HandleKendalaDetected.php
├── Services/
│   ├── TicketService.php
│   └── EscalationNotifier.php           ← routes notification to WA / email / both
├── Http/Controllers/
│   ├── TicketController.php
│   └── EscalationSettingsController.php ← tenant settings page
├── Notifications/
│   ├── EscalationViaEmail.php
│   └── EscalationViaWhatsApp.php
└── Models/SupportTicket.php
```

**Escalation notification settings** (tenant settings page):

Each tenant configures where they want to be notified when the AI detects a `%%KENDALA_DETECTED%%`:

```
Settings → Escalation & Notifications
  ┌─────────────────────────────────────────┐
  │ Notify via   [✓] WhatsApp  [✓] Email    │
  │                                         │
  │ WhatsApp number  +62 812-XXXX-XXXX      │
  │ Email address    admin@yourcompany.com   │
  │                                         │
  │ Notify on:                              │
  │   [✓] Complaint                         │
  │   [✓] Question (unanswerable)           │
  │   [✓] Escalation request               │
  │   [✓] Schedule change                  │
  └─────────────────────────────────────────┘
```

Stored in `tenant_notification_settings` table:

```sql
tenant_notification_settings
  id, tenant_id
  channel_wa(bool), wa_number(string, nullable)
  channel_email(bool), email(string, nullable)
  notify_on(json)          -- ["complaint","question","escalation","schedule_change"]
  created_at, updated_at
```

`EscalationNotifier` reads this config and fires the appropriate notification(s):
- **WA**: sends a formatted message to the configured number via the tenant's own WAHA session
- **Email**: sends via Laravel Notification (Mailgun/SMTP)
- Both can fire simultaneously

WA escalation message format (sent to owner/agent number):
```
🚨 *Eskalasi CS*
Pelanggan: {name} ({phone})
Tipe: Complaint / Question / Escalation
Masalah: {ac_problem}

Buka dashboard untuk ambil alih percakapan.
```

### Module: `LiveAgent`
**Responsibility:** Human takeover toggle per customer.

```
Modules/LiveAgent/
├── Http/Controllers/TakeoverController.php  ← admin toggles takeover
├── Services/TakeoverService.php
└── Models/Customer.php (shared reference)
```

Key logic:
- `GET /api/customers/check-takeover?phone=&from=` (replaces n8n "Checking Live Agent")
- Returns `{ is_human_takeover: bool }` — used in `Filter WA API message` equivalent

### Module: `Billing`
**Responsibility:** Plans, subscriptions, invoices, payments, dunning, and tenant suspension.

```
Modules/Billing/
├── Console/
│   └── ProcessBillingCommand.php        ← runs daily via scheduler
├── Services/
│   ├── SubscriptionService.php          ← create/cancel/reactivate
│   ├── InvoiceService.php               ← generate invoices, number them
│   ├── DunningService.php               ← send reminders at the right days
│   └── SuspensionService.php            ← suspend/reactivate tenants
├── Http/Controllers/
│   ├── BillingController.php            ← tenant: view invoices, upload proof
│   └── LandlordBillingController.php    ← landlord: verify payments, manage plans
├── Mail/
│   ├── InvoiceGenerated.php
│   ├── PaymentReminder.php              ← escalating urgency variants
│   └── TenantSuspended.php
├── Models/
│   ├── Plan.php
│   ├── Subscription.php
│   ├── Invoice.php
│   └── Payment.php
└── Middleware/
    └── EnsureTenantActive.php           ← blocks suspended tenants
```

Key logic — the daily `ProcessBillingCommand`:
1. **Generate invoices** — find subscriptions where `current_period_end = today`, create invoice, advance period dates
2. **Send reminders (dunning)** — find open invoices and send reminder based on days since `issued_at`:
   - Day 0: Invoice generated → send invoice email
   - Day 7: Gentle reminder
   - Day 14 (due date): Urgent reminder
   - Day 15: Mark subscription `past_due`
   - Day 22 (7-day grace): Call `SuspensionService::suspend(tenant)` → set `tenants.status = suspended`
3. **Check trial expiry** — if `trial_ends_at < now` and no active subscription, suspend

`EnsureTenantActive` middleware:
- Applied to the WA webhook route
- If `tenant.status = suspended`, return HTTP 200 (so WAHA doesn't retry) with no action
- Logs the blocked event for the landlord dashboard

`SuspensionService`:
- `suspend(tenant)`: sets `tenants.status = suspended`, sends `TenantSuspended` mail
- `reactivate(tenant)`: called when payment verified, resets status to `active`, re-opens current period

### Module: `Admin` (future)
Dashboard for tenant admins: view conversations, manage bookings, assign tickets, toggle live agent.

---

## 5. Request Lifecycle (replacing the n8n flow)

```
POST /webhook/{tenant}/{session}           [WebhookController]
  │
  ├─ Validate signature/header
  ├─ Parse IncomingMessage DTO
  ├─ Check: event == "message" AND NOT fromMe AND NOT group (@g.us)
  ├─ Check: tenant.status == active|trial            [EnsureTenantActive middleware]
  ├─ Check: customer.is_human_takeover == false     [LiveAgent module]
  │
  └─ Dispatch → ProcessMessageJob (queued)
       │
       ├─ Build prompt (PromptBuilder)
       ├─ Load conversation history (ConversationMemory)
       ├─ Call OpenRouter API (OpenRouterClient)
       ├─ Save assistant message to DB
       ├─ Parse output (OutputParser)
       │
       ├─ hasBooking?  → fire BookingConfirmed → BookingService::create()
       │                                       → WahaClient::sendText(confirmationMsg)
       │                                       → WahaClient::sendFile(invoice) [optional]
       │
       ├─ hasKendala?  → fire KendalaDetected  → TicketService::create()
       │                                       → WahaClient::sendText(humanMsg)
       │
       └─ default      → WahaClient::sendText(output)
```

---

## 6. Feature Config System (per tenant)

Stored in `tenant_features` table. Feature flags checked via a `FeatureManager` service.

```php
// Example usage
$features->isEnabled('booking');        // Can the bot take bookings?
$features->isEnabled('rag');            // Is knowledge base RAG active?
$features->isEnabled('invoice');        // Send invoice PDF after booking?
$features->isEnabled('live_agent');     // Human takeover feature?
$features->get('ai.model');             // Which model to use?
$features->get('ai.max_history');       // How many messages of history to send?
$features->get('wa.session');           // WA session name
```

Tenants can override global defaults. Config is cached (Redis/file) per tenant.

---

## 7. Key API Routes

```
# Public webhook (per tenant+session)
POST   /webhook/{tenant}/{session}

# Internal (authenticated, tenant-scoped)
GET    /api/customers/check-takeover
POST   /api/customers/{id}/takeover
DELETE /api/customers/{id}/takeover

# Webhooks from external (legacy Sejukin compat)
POST   /api/webhooks/orders
POST   /api/webhooks/supports

# Admin REST
GET    /api/bookings
GET    /api/support-tickets
GET    /api/conversations
PATCH  /api/bookings/{id}
PATCH  /api/support-tickets/{id}

# Tenant management (landlord only)
POST   /landlord/tenants
POST   /landlord/tenants/{id}/features
```

---

## 8. Tech Stack

| Concern | Choice |
|---|---|
| Framework | Laravel 11 |
| Multi-tenancy | stancl/tenancy (shared DB strategy) |
| Modules | nwidart/laravel-modules |
| Queue | Laravel Horizon + Redis |
| Cache (memory/session) | Redis |
| AI provider | OpenRouter (via HTTP, no SDK) |
| WA API | WAHA-compatible (self-hosted) |
| Auth | Laravel Sanctum (API tokens per tenant) |
| PDF invoice | barryvdh/laravel-dompdf |
| Payment gateway | Midtrans (primary) + manual bank transfer |
| Email (billing) | Laravel Mail + Mailgun/SMTP |
| Testing | PestPHP |

---

## 9. Conversation Memory Implementation

Replaces n8n's `Simple Memory` (buffer window keyed by phone).

```php
class ConversationMemory
{
    public function load(Conversation $conv, int $limit = 20): array
    {
        return $conv->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->latest()->limit($limit)->get()->reverse()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()->toArray();
    }

    public function append(Conversation $conv, string $role, string $content): void
    {
        $conv->messages()->create(compact('role', 'content'));
    }
}
```

Max history length is a feature config per tenant (`ai.max_history`, default 20).

---

## 10. OutputParser (replaces n8n Parse Booking JS)

```php
class OutputParser
{
    public function parse(string $output, string $phone, string $phoneLid, ?string $pushName): ParsedOutput
    {
        $hasBooking = str_contains($output, '%%BOOKING_CONFIRMED%%');
        $hasKendala = str_contains($output, '%%KENDALA_DETECTED%%');

        if ($hasBooking) {
            $json = $this->extract($output, '%%BOOKING_CONFIRMED%%', '%%END_BOOKING%%');
            $humanMsg = $this->beforeMarker($output, '%%BOOKING_CONFIRMED%%');
            $booking = $this->sanitizeBooking(json_decode($json, true), $phone, $phoneLid, $pushName);
            return new ParsedOutput(humanMessage: $humanMsg, hasBooking: true, booking: $booking);
        }

        if ($hasKendala) {
            // ... similar
        }

        return new ParsedOutput(humanMessage: $output);
    }
}
```

---

## 11. Billing & Subscription Deep Dive

### Subscription Lifecycle (State Machine)

```
         ┌─────────────────────────────────┐
         ▼                                 │ payment received → reactivate
[trial] ──expires──► [active] ──invoice──► [past_due] ──grace expired──► [suspended]
                        ▲                      │                               │
                        └────── reactivate ────┘                    cancelled  │
                                                                               ▼
                                                                          [cancelled]
```

**State transitions:**
| From | To | Trigger |
|---|---|---|
| `trial` | `active` | First payment received |
| `trial` | `suspended` | Trial expired, no payment |
| `active` | `past_due` | Invoice due date passed |
| `past_due` | `suspended` | Grace period (7 days) expired |
| `past_due` | `active` | Payment verified |
| `suspended` | `active` | Payment verified (reactivation) |
| `active/past_due` | `cancelled` | Tenant requests cancellation |

### Dunning Schedule (Billing Reminder Flow)

The daily `ProcessBillingCommand` (scheduled at midnight) drives everything:

```
Day 0  (billing_cycle_day)  → Generate invoice, email: "Invoice INV-XXXX for Rp X.XXX.XXX"
Day 7  (issued_at + 7)      → Reminder email: "Your invoice is due in 7 days"
Day 14 (due_at)             → Urgent email: "Invoice due today — please pay now"
Day 15 (due_at + 1)         → Mark subscription past_due, email: "Payment overdue — grace period started"
Day 22 (due_at + 8)         → SUSPEND: set tenant.status=suspended, email: "Service suspended"
Day 52 (due_at + 38)        → Final notice before data deletion (optional — good practice to warn)
```

> **Grace period is your generosity window.** 7 days is standard. Too short = angry clients; too long = you eat the cost.

### Invoice Number Format

```
INV-{YEAR}-{MONTH}-{SEQUENCE}
e.g. INV-2026-08-0042
```

Sequence is per-tenant-per-month, padded to 4 digits. Store the counter in `invoices` with a DB sequence or just `MAX(id)` filtered by month.

### Payment Flow (Indonesia-first)

Two paths depending on tenant preference:

**Path A — Manual Bank Transfer (default for B2B)**
```
Invoice generated → tenant uploads bukti transfer (proof_url)
→ landlord admin verifies in dashboard → click "Verify Payment"
→ Payment record created (status=verified) → Invoice marked paid
→ SuspensionService::reactivate(tenant)
```

**Path B — Midtrans (automated)**
```
Invoice generated → "Pay Now" link → Midtrans payment page
→ Midtrans webhook hits POST /billing/midtrans/webhook
→ PaymentController verifies signature → creates Payment record
→ Invoice marked paid → tenant auto-reactivated
```

Start with Path A (manual). It's how most Indonesian B2B SaaS starts — your clients will prefer WhatsApp confirmation anyway. Add Midtrans in a later phase.

### What Suspension Actually Does

When `SuspensionService::suspend($tenant)` is called:

1. Set `tenants.status = suspended`
2. Set `subscriptions.status = suspended`
3. Send `TenantSuspended` mail to `billing_contacts`
4. **Do NOT** delete data, disconnect WA session, or touch conversations

The `EnsureTenantActive` middleware handles the rest silently:
```php
// Applied to: POST /webhook/{tenant}/{session}
if (in_array($tenant->status, ['suspended', 'cancelled'])) {
    return response()->json(['status' => 'ok']); // 200 so WAHA stops retrying
}
```

The bot just stops responding — customers get no message. The tenant sees a "suspended" banner in their dashboard. When they pay and get reactivated, the bot resumes exactly where it left off.

### Plan Limits Enforcement

The `limits` JSON column on `plans` lets you restrict usage per plan:

```json
{
  "wa_sessions": 1,
  "messages_per_month": 3000,
  "ai_model": "openai/gpt-4o-mini",
  "live_agent": false,
  "booking": true,
  "rag": false
}
```

A `UsageService` tracks monthly message counts per tenant. If they hit the limit, the bot replies with an upgrade prompt (or just stops — your call). This is the "feature config per client" requirement fulfilled through the plan tier.

---

## 12. Implementation Phases

### Phase 1 — Core Infrastructure
- [ ] Laravel 11 app scaffold
- [ ] stancl/tenancy setup (shared DB)
- [ ] nwidart/laravel-modules setup
- [ ] Core migrations (tenants, customers, conversations, messages)
- [ ] Redis queue + Horizon
- [ ] `FeatureManager` service

### Phase 2 — WA Integration
- [ ] `WhatsApp` module: WebhookController + WahaClient
- [ ] IncomingMessage DTO + validation
- [ ] Signature/header auth middleware per tenant
- [ ] `MessageReceived` event

### Phase 3 — AI Engine
- [ ] `AI` module: OpenRouterClient + ConversationMemory + PromptBuilder
- [ ] `OutputParser` (booking + kendala detection)
- [ ] `ProcessMessageJob` wiring everything together
- [ ] Store all messages to DB

### Phase 4 — Booking + Support
- [ ] `Booking` module: model, service, listeners
- [ ] `Support` module: ticket model, service, listeners
- [ ] `LiveAgent` module: takeover toggle + check endpoint
- [ ] product_categories + products migrations + seeder (Sejukin data)

### Phase 5 — Tenant Management
- [ ] Tenant CRUD (landlord)
- [ ] Landlord / Client page (admin login) — UI for landlord to manage tenants and tenant-level users
- [ ] Onboard flow: when creating a tenant, also create an initial tenant admin user (name, email, invite link) and seed default feature flags
- [ ] Feature config UI (simple JSON editor or form) — tenant-scoped feature flags and overrides
- [ ] WA session config per tenant
- [ ] AI config per tenant (model, system prompt)
- [ ] Knowledge base management UI: CRUD for tenant knowledge_bases, document upload, RAG enable/disable per KB

### Phase 6 — Admin Dashboard
- [ ] Conversation list + detail view (Livewire or Inertia/Vue)
- [ ] Booking management
- [ ] Support ticket management
- [ ] Live agent toggle per customer

### Phase 7 — Billing (Core)
- [ ] `Billing` module: Plan, Subscription, Invoice, Payment models + migrations
- [ ] `EnsureTenantActive` middleware wired to webhook route
- [ ] `InvoiceService`: generate invoice, assign number, send email
- [ ] `DunningService`: reminder emails at Day 7 / Day 14 / Day 15 / Day 22
- [ ] `SuspensionService`: suspend + reactivate
- [ ] `ProcessBillingCommand` scheduled daily (covers generation + dunning + suspension)
- [ ] Landlord admin: verify manual bank transfer payments
- [ ] Tenant billing page: view invoices, upload transfer proof

### Phase 8 — Polish & Production
- [ ] Midtrans payment integration (automated path)
- [ ] Invoice PDF generation (dompdf) + email attachment
- [ ] WA invoice file send after booking
- [ ] Webhook retry / dead-letter queue handling
- [ ] Rate limiting per tenant (messages_per_month from plan limits)
- [ ] Observability (logging, Telescope)
- [ ] Plan upgrade/downgrade flow

---

## 13. Sejukin Indonesia as Tenant 1 (Reference Implementation)

The existing Sejukin workflow is seeded as Tenant 1:
- System prompt: migrated verbatim from the n8n knowledge_base tool description
- Products: seeded from the JSON in the n8n prompt (booking feature enabled for this tenant only)
- WA session: `sejukin-indonesia`
- Features: `booking=true` (Sejukin-specific), `kendala=true`, `live_agent=true`, `invoice=false`, `rag=false`
- Escalation: WA to owner number + email

New clients onboarded with `booking=false` by default — the booking marker instructions are only injected into the system prompt when the feature flag is on.

---

## Notes & Open Concerns

### Prompt modularity (important)
The `%%BOOKING_CONFIRMED%%` marker block is part of the system prompt. When `booking=false`, `PromptBuilder` must NOT inject that section — otherwise the AI will try to emit booking JSON that nothing handles. The system prompt is assembled from composable blocks, not stored as one monolithic text.

### Escalation → Takeover gap
When the AI detects a kendala and notifies the agent via WA/email, the agent still needs to manually open the dashboard and toggle `is_human_takeover` to take over the conversation. Consider a future shortcut: a magic-link in the WA notification that one-taps takeover from the phone. Not a blocker for v1 but worth knowing.

### WAHA session for outbound escalation
Escalation WA notifications go out through the tenant's own WAHA session (same bot number). The receiving number (agent's WA) must be different from the bot's number — document this clearly in onboarding.

### Shared DB message volume
The `messages` table will grow fast (every AI exchange is 2+ rows). Add a composite index on `(tenant_id, conversation_id, created_at)` from day one. Plan for archival/partitioning when any tenant hits ~500K messages.

### "Fully adjustable" scope boundary
Feature flags handle on/off toggling. But truly custom behavior (e.g., a client who wants a 3-step booking flow instead of the marker protocol) requires prompt engineering, not code changes. Set this expectation with clients: the platform is configurable via prompt + feature flags, not infinitely programmable per client without dev work.
