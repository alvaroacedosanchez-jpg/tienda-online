<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('tipo');

        $events = $this->filtered($request)
            ->with(['user', 'order', 'product', 'variant', 'invoice', 'ticket', 'payment'])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $types = Event::select('type')->distinct()->orderBy('type')->pluck('type');

        return view('admin.events.index', compact('events', 'types', 'type'));
    }

    /** Exportación JSON pensada para que otro sistema la consuma (Tarea 2) o Power BI. */
    public function json(Request $request)
    {
        $events = $this->filtered($request)
            ->with(['user', 'order', 'product', 'variant', 'invoice', 'ticket', 'payment'])
            ->orderBy('id')
            ->get()
            ->map(fn (Event $e) => [
                'id'                 => $e->id,
                'occurred_at'        => $e->occurred_at?->toIso8601String(),
                'type'               => $e->type,
                'session_id'         => $e->session_id,
                'user_id'            => $e->user_id,
                'user_name'          => $e->user?->name,
                'user_email'         => $e->user?->email,
                'order_id'           => $e->order_id,
                'order_reference'    => $e->order?->reference,
                'product_id'         => $e->product_id,
                'product_name'       => $e->product?->name,
                'product_sku'        => $e->product?->sku,
                'product_variant_id' => $e->product_variant_id,
                'variant_size'       => $e->variant?->size,
                'quantity'           => $e->quantity,
                'previous_quantity'  => $e->previous_quantity,
                'unit_price'         => $e->unit_price,
                'amount'             => $e->amount,
                'status'             => $e->status,
                'previous_status'    => $e->previous_status,
                'origin'             => $e->origin,
                'payment_id'         => $e->payment_id,
                'payment_method'     => $e->payment_method,
                'gateway'            => $e->gateway,
                'response_code'      => $e->response_code,
                'invoice_id'         => $e->invoice_id,
                'invoice_number'     => $e->invoice?->number,
                'support_ticket_id'  => $e->support_ticket_id,
                'ticket_subject'     => $e->ticket?->subject,
                'discount_code'      => $e->discount_code,
                'payload'            => $e->payload,
            ]);

        return response()->json(['count' => $events->count(), 'events' => $events], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function csv(Request $request)
    {
        $events = $this->filtered($request)
            ->with(['user', 'order', 'product', 'variant', 'invoice', 'ticket', 'payment'])
            ->orderBy('id')
            ->get();

        return response()->streamDownload(function () use ($events) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel/Power BI

            fputcsv($out, [
                'id',
                'occurred_at',
                'type',
                'session_id',
                'user_id',
                'user_name',
                'user_email',
                'order_id',
                'order_reference',
                'product_id',
                'product_name',
                'product_sku',
                'product_variant_id',
                'variant_size',
                'quantity',
                'previous_quantity',
                'unit_price',
                'amount',
                'status',
                'previous_status',
                'origin',
                'payment_id',
                'payment_method',
                'gateway',
                'response_code',
                'invoice_id',
                'invoice_number',
                'support_ticket_id',
                'ticket_subject',
                'discount_code',
                'payload',
            ], ';');

            foreach ($events as $e) {
                fputcsv($out, [
                    $e->id,
                    $e->occurred_at?->toIso8601String(),
                    $e->type,
                    $e->session_id,
                    $e->user_id,
                    $e->user?->name,
                    $e->user?->email,
                    $e->order_id,
                    $e->order?->reference,
                    $e->product_id,
                    $e->product?->name,
                    $e->product?->sku,
                    $e->product_variant_id,
                    $e->variant?->size,
                    $e->quantity,
                    $e->previous_quantity,
                    $e->unit_price,
                    $e->amount,
                    $e->status,
                    $e->previous_status,
                    $e->origin,
                    $e->payment_id,
                    $e->payment_method,
                    $e->gateway,
                    $e->response_code,
                    $e->invoice_id,
                    $e->invoice?->number,
                    $e->support_ticket_id,
                    $e->ticket?->subject,
                    $e->discount_code,
                    $e->payload ? json_encode($e->payload, JSON_UNESCAPED_UNICODE) : '',
                ], ';');
            }
            fclose($out);
        }, 'eventos.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request)
    {
        return Event::query()
            ->when($request->query('tipo'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->query('desde'), fn ($q, $d) => $q->where('occurred_at', '>=', $d));
    }
}