<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.customers.index', compact('customers'));
    }
}