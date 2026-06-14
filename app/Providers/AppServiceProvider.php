<?php

namespace App\Providers;

use App\Services\PurchaseOrderDueReceivingService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $dueReceivingOrders = $user
                ? app(PurchaseOrderDueReceivingService::class)->ordersDueForReceiving($user)
                : collect();

            $view->with('dueReceivingOrders', $dueReceivingOrders);
        });
    }
}
