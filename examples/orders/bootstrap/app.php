<?php

use App\Providers\ServicesProvider;
use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([ServicesProvider::class])
    ->withRouting(commands: __DIR__.'/../routes/console.php')
    ->create();
