<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Artisan;

Artisan::command('customers:create {name}', function (string $name) {
    $this->info('Customer '.Customer::query()->create(['name' => $name])->id.' created.');
});
