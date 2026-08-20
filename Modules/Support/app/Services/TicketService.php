<?php

namespace Modules\Support\Services;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\SupportTicket;

class TicketService
{
    public function create(Customer $customer, Conversation $conversation, array $data): SupportTicket
    {
        return SupportTicket::create([
            'customer_id'     => $customer->id,
            'conversation_id' => $conversation->id,
            'customer_name'   => $data['customer_name'] ?? $customer->name ?? $customer->push_name,
            'customer_phone'  => $data['customer_phone'] ?? $customer->phone,
            'ac_problem'      => $data['ac_problem'] ?? null,
            'kendala_type'    => $data['kendala_type'] ?? 'question',
            'status'          => 'open',
        ]);
    }
}
