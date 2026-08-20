<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;

class LiveAgentController extends Controller
{
    public function enable(Customer $customer)
    {
        $customer->update(['is_human_takeover' => true, 'takeover_agent_id' => Auth::id()]);
        return back()->with('success', 'Live agent enabled for ' . ($customer->name ?? $customer->phone));
    }

    public function disable(Customer $customer)
    {
        $customer->update(['is_human_takeover' => false, 'takeover_agent_id' => null]);
        return back()->with('success', 'Bot resumed for ' . ($customer->name ?? $customer->phone));
    }
}
