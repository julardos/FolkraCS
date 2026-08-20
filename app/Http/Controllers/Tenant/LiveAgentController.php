<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class LiveAgentController extends Controller
{
    public function enable(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->update(['is_human_takeover' => true, 'takeover_agent_id' => Auth::id()]);
        return back()->with('success', 'Live agent mode enabled.');
    }

    public function disable(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->update(['is_human_takeover' => false, 'takeover_agent_id' => null]);
        return back()->with('success', 'Bot resumed.');
    }

    private function authorizeCustomer(Customer $customer): void
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);
        abort_if($customer->client_id !== $client->id, 403);
    }
}
