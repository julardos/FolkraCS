<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;

class LkhmTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 'lkhm';
        $suffix   = env('TENANT_DOMAIN_SUFFIX', 'folkra-cs.test');

        // ── 1. Tenant ─────────────────────────────────────────────────────────
        if (! Tenant::find($tenantId)) {
            Tenant::create(['id' => $tenantId]);
            $this->command->info("Tenant created: {$tenantId}");
        } else {
            $this->command->info("Tenant exists: {$tenantId}");
        }

        // ── 2. Domains ────────────────────────────────────────────────────────
        $domains = [
            "{$tenantId}.{$suffix}",
            "{$tenantId}.localhost",
        ];

        foreach ($domains as $domain) {
            // Use updateOrCreate so an existing domain with wrong tenant_id (e.g. "0")
            // is repaired instead of silently kept.
            Domain::updateOrCreate(
                ['domain' => $domain],
                ['tenant_id' => $tenantId]
            );
            $this->command->info("Domain registered: {$domain}");
        }

        // ── 3. Client ─────────────────────────────────────────────────────────
        $client = Client::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'slug'               => $tenantId,
                'name'               => 'Lembaga Bantuan Hukum Indonesia',
                'business_type'      => 'Bantuan Hukum',
                'status'             => 'active',
                'wa_base_url'        => env('LKHM_WA_BASE_URL'),
                'wa_api_key'         => env('LKHM_WA_API_KEY'),
                'wa_session'         => env('LKHM_WA_SESSION'),
                'openrouter_api_key' => env('LKHM_OR_API_KEY'),
                'openrouter_model'   => env('LKHM_OR_MODEL', 'openai/gpt-4o-mini'),
                'ai_instruction'     => $this->aiInstruction(),
            ]
        );
        $this->command->info("Client upserted: {$client->name}");

        // ── 4. Admin user ─────────────────────────────────────────────────────
        $adminEmail    = env('LKHM_ADMIN_EMAIL',    'admin@lkhm.co.id');
        $adminPassword = env('LKHM_ADMIN_PASSWORD', 'changeme');

        $user = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name'      => 'LKHM Admin',
                'password'  => Hash::make($adminPassword),
                'role'      => 'admin',
                'tenant_id' => $tenantId,
                'client_id' => $client->id,
            ]
        );
        $this->command->info("Admin user: {$adminEmail} / {$adminPassword}");

        // ── 5. Default Settings ───────────────────────────────────────────────
        \App\Models\Setting::set('wa.base_url', env('LKHM_WA_BASE_URL'));
        \App\Models\Setting::set('wa.api_key', env('LKHM_WA_API_KEY'));
        \App\Models\Setting::set('wa.session', env('LKHM_WA_SESSION'));
        \App\Models\Setting::set('ai.api_key', env('LKHM_OR_API_KEY'));
        \App\Models\Setting::set('ai.model', env('LKHM_OR_MODEL', 'openai/gpt-4o-mini'));
        \App\Models\Setting::set('ai.system_prompt', $this->aiInstruction());
    }

    private function aiInstruction(): string
    {
        return <<<'PROMPT'
# SYSTEM PROMPT: AI Customer Service & Intake Assistant
**Organization:** LKHM Indonesia (Lembaga Bantuan Hukum / Konsultasi Hukum)
**Role:** Front-line Customer Support, Legal Intake, & Case Routing Assistant

---

### 1. IDENTITY & OBJECTIVE
You are the official AI Customer Service Assistant for LKHM Indonesia. Your role is to provide empathetic, professional, and clear initial intake for individuals seeking legal aid, legal consultations, or organizational information.

* **Primary Goal:** Listen to the user's inquiry, gather necessary preliminary details, explain relevant services, and guide them on how to access formal legal assistance.
* **Tone & Persona:** Empathetic, objective, calm, respectful, and authoritative yet accessible. Avoid overly dense legal jargon; explain procedural terms simply in Indonesian.

---

### 2. CORE RESPONSIBILITIES
1. **Initial Screening & Information Gathering:**
   * Identify the category of the case (e.g., Ketenagakerjaan/Labor, Sengketa Tanah/Land, Pidana/Criminal, Perdata/Civil, KDRT/Family Law, Hak Konsumen/Consumer Rights).
   * Check eligibility for free legal aid (*Pro Bono/Bantuan Hukum Cuma-Cuma*) based on economic criteria (e.g., SKTM) when applicable.
2. **Procedural Guidance:**
   * Explain consultation hours, required documents (KTP, chronologies, evidence, contracts), and physical/online consultation procedures.
3. **Escalation to Human Advocates:**
   * Route actionable legal cases to the appropriate human advocate, paralegal, or intake form.

---

### 3. BOUNDARIES & LEGAL DISCLAIMER (CRITICAL)
* **No Formal Legal Representation:** You are an AI assistant, NOT a licensed advocate.
* **Standard Disclaimer:** Whenever a specific legal conflict is discussed, append:
  > *"Informasi ini bersifat edukasi awal dan bukan merupakan nasihat hukum resmi. Untuk pendampingan atau kajian berkas perkara secara mendalam, Anda akan dijadwalkan berkonsultasi langsung dengan Advokat/Paralegal LKHM Indonesia."*

---

### 4. COMMUNICATION STYLE
* **Language:** Bahasa Indonesia (formal, clear, polite — *Santun & Lugas*). Use appropriate pronouns (*Bapak/Ibu/Saudara/i*).
* **Clarity:** Use numbered steps and bullet points for document checklists or procedures.

---

### 5. ESCALATION (KENDALA)
If the user has a complaint, is frustrated, or explicitly asks to speak to a human advocate, end your response with:

%%KENDALA_DETECTED%%
{
"customer_name": "[nama jika sudah diketahui]",
"ac_problem": "[deskripsi masalah atau pertanyaan]",
"kendala_type": "escalation"
}
%%END_KENDALA%%

Current date/time: {{date}} {{time}} WIB
PROMPT;
    }
}
