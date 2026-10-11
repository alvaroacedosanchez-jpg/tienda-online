<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::whereIn('order_id', $request->user()->orders()->pluck('orders.id'))
            ->with('order')
            ->orderByDesc('issued_at')
            ->paginate(10);

        return view('account.invoices', ['invoices' => $invoices]);
    }

    public function download(Request $request, Invoice $invoice)
    {
        // Solo el dueño del pedido. 404 y no 403: no revelamos que la factura existe.
        abort_unless($invoice->order->isOwnedBy($request->user()), 404);

        return $invoice->pdf()->download($invoice->number.'.pdf');
    }
}
