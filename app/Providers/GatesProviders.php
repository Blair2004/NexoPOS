<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// not done
use App\Gates\Categories;
use App\Gates\Products;
use App\Gates\Expenses;
use App\Gates\Orders;
use App\Gates\Providers;
use App\Gates\Supplies;

class GatesProviders extends ServiceProvider
{
    public function boot()
    {
        /**
         * register application gates
         */
        foreach([
            'categories', 
            'products', 
            'orders', 
            'expenses',
            'taxes',
            'procurements',
            'suppliers',
            'customers',
            'customers-groups',
            'orders-coupons',
            'registers',
            'reward-system',
            'store',
            'stock-transfer',
        ] as $namespace ) {
            foreach([
                'create', 'edit', 'delete', 'view'
            ] as $action ) {
                $className  =   'App\Gates\\' . ucwords( Str::camel( $namespace ) );
                Gate::define( 
                    'nexopos.' . $action . '.' . $namespace, 
                    $className . '@' . Str::camel( $action ) 
                );
            }
        }
    }
}