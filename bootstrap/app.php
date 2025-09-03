<?php

use App\Models\Dept;
use App\Models\Admin;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Category;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Policies\DeptPolicy;
use App\Policies\AdminPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\ProductPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ShipmentPolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth' => \App\Http\Middleware\AuthenticateMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            // 'proxies' => \App\Http\Middleware\TrustProxies::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

    })
        ->booted(function() {

            Gate::policy(Admin::class, AdminPolicy::class);
            Gate::policy(Category::class, CategoryPolicy::class);
            Gate::policy(Product::class, ProductPolicy::class);
            Gate::policy(Dept::class, DeptPolicy::class);
            Gate::policy(Shipment::class, ShipmentPolicy::class);
            Gate::policy(Invoice::class, InvoicePolicy::class);
            Gate::policy(Supplier::class, SupplierPolicy::class);


    })->create();


