<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EscalationController extends Controller
{
    public function index()
    {
        $client   = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);
        $settings = NotificationSetting::where('client_id', $client->id)->first();

        return Inertia::render('Tenant/Escalation', [
            'settings' => $settings ? [
                'channel_wa'    => $settings->channel_wa,
                'wa_number'     => $settings->wa_number,
                'channel_email' => $settings->channel_email,
                'email'         => $settings->email,
                'notify_on'     => $settings->notify_on ?? ['complaint','question','escalation','schedule_change'],
            ] : [
                'channel_wa'    => true,
                'wa_number'     => null,
                'channel_email' => false,
                'email'         => null,
                'notify_on'     => ['complaint','question','escalation','schedule_change'],
            ],
        ]);
    }

    public function update(Request $request)
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);

        $data = $request->validate([
            'channel_wa'    => 'boolean',
            'wa_number'     => 'nullable|string|max:20',
            'channel_email' => 'boolean',
            'email'         => 'nullable|email',
            'notify_on'     => 'array',
            'notify_on.*'   => 'in:complaint,question,escalation,schedule_change',
        ]);

        // Validate wa_number doesn't match bot number
        if (!empty($data['wa_number']) && !empty($client->wa_session)) {
            $botNumber = preg_replace('/\D/', '', $client->wa_session);
            if ($data['wa_number'] === $botNumber) {
                return back()->withErrors(['wa_number' => 'Escalation number cannot be the same as the bot session number.']);
            }
        }

        NotificationSetting::updateOrCreate(
            ['client_id' => $client->id],
            $data
        );

        return back()->with('success', 'Escalation settings saved.');
    }
}
