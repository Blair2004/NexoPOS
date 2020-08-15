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
use App\Crud\UnitCrud;
use App\Crud\UnitGroupCrud;
use App\Crud\ProductCategoryCrud;
use App\Crud\ProductCrud;
use App\Crud\TaxCrud;
use App\Crud\TaxesGroupCrud;
use App\Crud\UserCrud;
use App\Crud\ProcurementCrud;
use App\Crud\RegisterCrud;
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
                case 'ns.units-groups': return UnitGroupCrud::class;
                case 'ns.units': return UnitCrud::class;
                case 'ns.products': return ProductCrud::class;
                case 'ns.products-categories': return ProductCategoryCrud::class;
                case 'ns.taxes': return TaxCrud::class;
                case 'ns.taxes-groups': return TaxesGroupCrud::class;
                case 'ns.users': return UserCrud::class;
                case 'ns.registers': return RegisterCrud::class;
                case 'ns.procurements': return ProcurementCrud::class;
            }
            return $namespace;
        });

    }
}
