<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\ExpenseCategory;
use App\Models\InstallmentPayment;
use App\Models\OperationExpense;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\Service;
use App\Models\Supplier;
use App\Policies\BrandPolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\InstallmentPaymentPolicy;
use App\Policies\OperationExpensePolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalePolicy;
use App\Policies\ServicePolicy;
use App\Policies\SupplierPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Sale::class               => SalePolicy::class,
        InstallmentPayment::class => InstallmentPaymentPolicy::class,
        PurchaseOrder::class      => PurchaseOrderPolicy::class,
        Product::class            => ProductPolicy::class,
        Brand::class              => BrandPolicy::class,
        Supplier::class           => SupplierPolicy::class,
        Service::class            => ServicePolicy::class,
        ExpenseCategory::class    => ExpenseCategoryPolicy::class,
        OperationExpense::class   => OperationExpensePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
