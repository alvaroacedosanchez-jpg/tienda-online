<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;

class TicketController extends Controller
{
    public function index()
    {
        return view('admin.tickets.index', [
            'tickets' => SupportTicket::with('order')->orderByDesc('id')->paginate(20),
        ]);
    }
}
