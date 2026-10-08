<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SupportTicket;
use App\Services\EventLogger;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function create(Request $request)
    {
        return view('support.create', ['reference' => $request->query('pedido')]);
    }

    public function store(Request $request, EventLogger $events)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'order_reference' => ['nullable', 'string', 'max:40'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $order = ! empty($data['order_reference'])
            ? Order::where('reference', trim($data['order_reference']))->first()
            : null;

        if (! empty($data['order_reference']) && ! $order) {
            return back()->withInput()->withErrors(['order_reference' => 'No encontramos ningún pedido con esa referencia.']);
        }

        $ticket = SupportTicket::create([
            'order_id' => $order?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        $events->log(EventLogger::SUPPORT_REQUESTED, [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'order_reference' => $order?->reference,
        ], $order?->id);

        return redirect()->route('support.create')->with('status', 'Solicitud enviada. Número de ticket: #'.$ticket->id);
    }
}
