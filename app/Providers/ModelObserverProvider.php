<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// models
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerGroup;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\OrderCoupon;
use App\Models\OrderPayment;
use App\Models\Procurement;
use App\Models\ProcurementProduct;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\ProductCategory;
use App\Models\ProductTax;
use App\Models\ProductVariation;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\RewardSystem;
use App\Models\Provider;
use App\Models\RewardSystemRule;
use App\Models\Transfer;
use App\Models\TransferProduct;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Observers\ProcurementProductObserver;
use App\Observers\RewardSystemObserver;
// observers
use App\Observers\UUIDObserver;

class ModelObserverProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        /**
         * UUID Generator, to generate
         * unique model uuid while creating
         */
        Customer::observe( UUIDObserver::class );
        CustomerAddress::observe( UUIDObserver::class );
        CustomerGroup::observe( UUIDObserver::class );
        Expense::observe( UUIDObserver::class );
        ExpenseCategory::observe( UUIDObserver::class );
        Order::observe( UUIDObserver::class );
        OrderCoupon::observe( UUIDObserver::class );
        OrderPayment::observe( UUIDObserver::class );
        Procurement::observe( UUIDObserver::class );
        ProcurementProduct::observe( UUIDObserver::class );
        ProcurementProduct::observe( ProcurementProductObserver::class );
        Product::observe( UUIDObserver::class );
        ProductHistory::observe( UUIDObserver::class );
        ProductCategory::observe( UUIDObserver::class );
        ProductTax::observe( UUIDObserver::class );
        ProductVariation::observe( UUIDObserver::class );
        ProductUnitQuantity::observe( UUIDObserver::class );
        Provider::observe( UUIDObserver::class );
        Register::observe( UUIDObserver::class );
        RegisterHistory::observe( UUIDObserver::class );
        RewardSystem::observe( UUIDObserver::class );
        RewardSystem::observe( RewardSystemObserver::class );
        RewardSystemRule::observe( UUIDObserver::class );
        Transfer::observe( UUIDObserver::class );
        TransferProduct::observe( UUIDObserver::class );
        Tax::observe( UUIDObserver::class );
        Unit::observe( UUIDObserver::class );
        UnitGroup::observe( UUIDObserver::class );
    }
}