<?php

namespace App\Providers;

use App\Crud\CouponCrud;
use App\Crud\CustomerAccountCrud;
use App\Crud\CustomerCouponCrud;
use App\Crud\CustomerCrud;
use App\Crud\CustomerGroupCrud;
use App\Crud\CustomerOrderCrud;
use App\Crud\CustomerRewardCrud;
use App\Crud\GlobalProductHistoryCrud;
use App\Crud\HoldOrderCrud;
use App\Crud\OrderCrud;
use App\Crud\OrderInstalmentCrud;
use App\Crud\PartiallyPaidOrderCrud;
use App\Crud\PaymentTypeCrud;
use App\Crud\ProcurementCrud;
use App\Crud\ProcurementProductCrud;
use App\Crud\ProductCategoryCrud;
use App\Crud\ProductCrud;
use App\Crud\ProductHistoryCrud;
use App\Crud\ProductUnitQuantitiesCrud;
use App\Crud\ProviderCrud;
use App\Crud\ProviderProcurementsCrud;
use App\Crud\ProviderProductsCrud;
use App\Crud\RegisterCrud;
use App\Crud\RegisterHistoryCrud;
use App\Crud\RewardSystemCrud;
use App\Crud\RolesCrud;
use App\Crud\TaxCrud;
use App\Crud\TaxesGroupCrud;
use App\Crud\TransactionAccountCrud;
use App\Crud\TransactionCrud;
use App\Crud\TransactionsHistoryCrud;
use App\Crud\UnitCrud;
use App\Crud\UnitGroupCrud;
use App\Crud\UnpaidOrderCrud;
use App\Crud\UserCrud;
use App\Services\ModulesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
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
        /**
         * every crud class on the system should be
         * added here in order to be available and supported.
         */
        Hook::addFilter('ns-crud-resource', function ($namespace) {
            /**
             * We'll attempt autoloading crud that explicitely
             * defined they want to be autoloaded. We expect classes to have 2
             * constant: AUTOLOAD=true, IDENTIFIER=<string>.
             */
            $classes = Cache::get('crud-classes', function () {
                $files = collect(Storage::disk('ns')->files('app/Crud'));

                return $files->map(fn($file) => 'App\Crud\\' . pathinfo($file)[ 'filename' ])
                    ->filter(fn($class) => (defined($class . '::AUTOLOAD') && defined($class . '::IDENTIFIER')));
            });

            /**
             * We pull the cached classes and checks if the
             * class has autoload and identifier defined.
             */
            $class = collect($classes)->filter(fn($class) => $class::AUTOLOAD && $class::IDENTIFIER === $namespace);

            if ($class->count() === 1) {
                return $class->first();
            }

            /**
             * We'll attempt to perform the same autoload
             * but for only enabled modules
             *
             * @var ModulesService $modulesService
             */
            $modulesService = app()->make(ModulesService::class);

            $classes = collect($modulesService->getEnabled())->map(function ($module) use ($namespace) {
                $classes = Cache::get('modules-crud-classes-' . $module[ 'namespace' ], function () use ($module) {
                    $files = collect(Storage::disk('ns')->files('modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Crud'));

                    return $files->map(fn($file) => 'Modules\\' . $module[ 'namespace' ] . '\Crud\\' . pathinfo($file)[ 'filename' ])
                        ->filter(fn($class) => (defined($class . '::AUTOLOAD') && defined($class . '::IDENTIFIER')));
                });

                /**
                 * We pull the cached classes and checks if the
                 * class has autoload and identifier defined.
                 */
                $class = collect($classes)->filter(fn($class) => $class::AUTOLOAD && $class::IDENTIFIER === $namespace);

                if ($class->count() === 1) {
                    return $class->first();
                }

                return false;
            })->filter();

            /**
             * If the namespace match a module crud instance,
             * we'll use that first result
             */
            if ($classes->isNotEmpty()) {
                return $classes->flatten()->first();
            }

            /**
             * We'll still allow users to define crud
             * manually from this section.
             */
            return match ($namespace) {
                'ns.orders' => OrderCrud::class,
                'ns.orders-instalments' => OrderInstalmentCrud::class,
                'ns.payments-types' => PaymentTypeCrud::class,
                'ns.hold-orders' => HoldOrderCrud::class,
                'ns.unpaid-orders' => UnpaidOrderCrud::class,
                'ns.partially-paid-orders' => PartiallyPaidOrderCrud::class,
                'ns.coupons' => CouponCrud::class,
                'ns.customers' => CustomerCrud::class,
                'ns.customers-groups' => CustomerGroupCrud::class,
                'ns.customers-rewards' => CustomerRewardCrud::class,
                'ns.customers-orders' => CustomerOrderCrud::class,
                'ns.customers-coupons' => CustomerCouponCrud::class,
                'ns.rewards-system' => RewardSystemCrud::class,
                'ns.providers' => ProviderCrud::class,
                'ns.transactions-accounts' => TransactionAccountCrud::class,
                'ns.transactions-history' => TransactionsHistoryCrud::class,
                'ns.transactions' => TransactionCrud::class,
                'ns.units-groups' => UnitGroupCrud::class,
                'ns.units' => UnitCrud::class,
                'ns.products' => ProductCrud::class,
                'ns.products-categories' => ProductCategoryCrud::class,
                'ns.products-units' => ProductUnitQuantitiesCrud::class,
                'ns.products-histories' => ProductHistoryCrud::class,
                'ns.taxes' => TaxCrud::class,
                'ns.taxes-groups' => TaxesGroupCrud::class,
                'ns.users' => UserCrud::class,
                'ns.registers' => RegisterCrud::class,
                'ns.registers-hitory' => RegisterHistoryCrud::class,
                'ns.procurements' => ProcurementCrud::class,
                'ns.procurements-products' => ProcurementProductCrud::class,
                'ns.roles' => RolesCrud::class,
                'ns.global-products-history' => GlobalProductHistoryCrud::class,
                'ns.providers-procurements' => ProviderProcurementsCrud::class,
                'ns.customers-account-history' => CustomerAccountCrud::class,
                'ns.providers-products' => ProviderProductsCrud::class,
                default => $namespace,
            };
        });
    }
}
