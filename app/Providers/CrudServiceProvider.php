<?php

namespace App\Providers;

use App\Crud\CouponCrud;
use App\Crud\CustomerCrud;
use App\Crud\CustomerGroupCrud;
use App\Crud\ExpenseCategoryCrud;
use App\Crud\ExpenseCrud;
use App\Crud\OrderCrud;
use App\Crud\ProviderCrud;
use App\Crud\RewardSystemCrud;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Hook;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Hook::addFilter( 'ns.crud-resource', function( $namespace ) {
            switch( $namespace ) {
                case 'ns.orders': return OrderCrud::class;
                case 'ns.coupons': return CouponCrud::class;
                case 'ns.customers': return CustomerCrud::class;
                case 'ns.customers-groups': return CustomerGroupCrud::class;
                case 'ns.customers-coupons': return CustomerCouponCrud::class;
                case 'ns.rewards-system': return RewardSystemCrud::class;
                case 'ns.providers': return ProviderCrud::class;
                case 'ns.expenses-categories': return ExpenseCategoryCrud::class;
                case 'ns.expenses': return ExpenseCrud::class;
            }
            return $namespace;
        });

    }
}
