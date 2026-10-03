<?php

use App\Models\Customer;
use Foundation\Billing\Contracts\BillingService;
use Illuminate\Support\Facades\Artisan;

Artisan::command('customers:show {id}', function (BillingService $billing, int $id) {
    $this->line('copy: '.(Customer::query()->find($id)->name ?? '-'));
    $this->line('rpc: '.($billing->customerName($id) ?? '-'));
});
