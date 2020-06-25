<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

use Tendoo\Core\Services\Options;
use Tendoo\Core\Services\DateService;

use App\Services\TaxService;
use App\Services\Validation;
use App\Services\UnitService;
use App\Services\ExpenseService;
use App\Services\ProductService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use App\Services\ProviderService;
use App\Services\ProcurementService;
use App\Services\ProductCategoryService;
use App\Services\OrdersService;

class ServiceProvider extends LaravelServiceProvider
{
    public function register()
    {
        $this->app->singleton( ProductCategoryService::class, function( $app ) {
            return new ProductCategoryService;
        });

        $this->app->singleton( TaxService::class, function( $app ) {
            return new TaxService( 
                $app->make( CurrencyService::class )
            );
        });

        $this->app->singleton( CurrencyService::class, function( $app ) {
            $options    =   app()->make( Options::class );
            return new CurrencyService( 
                0, [
                    'decimal_precision'     =>  $options->get( 'nexopos_decimal_precision', 2 ),
                    'thousand_separator'    =>  $options->get( 'nexopos_thousand_separator', '.' ),
                    'decimal_separator'     =>  $options->get( 'nexopos_decimal_separator', ',' ),
                    'currency'              =>  $options->get( 'nexopos_currency', 'USD' ),
                ]                
            );
        });

        $this->app->singleton( ProductService::class, function( $app ) {
            return new ProductService( 
                $app->make( ProductCategoryService::class ),
                $app->make( TaxService::class ),
                $app->make( CurrencyService::class ),
                $app->make( UnitService::class )
            );
        });

        $this->app->singleton( Validation::class, function( $app ) {
            return new Validation();
        });

        $this->app->singleton( UnitService::class, function( $app ) {
            return new UnitService(
                $app->make( CurrencyService::class )
            );
        });

        $this->app->singleton( ProviderService::class, function( $app ) {
            return new ProviderService();
        });

        $this->app->singleton( CustomerService::class, function( $app ) {
            return new CustomerService();
        });

        $this->app->singleton( ExpenseService::class, function( $app ) {
            return new ExpenseService();
        });

        $this->app->singleton( OrdersService::class, function( $app ) {
            return new OrdersService(
                $app->make( CustomerService::class ),
                $app->make( ProductService::class ),
                $app->make( UnitService::class ),
                $app->make( DateService::class ),
                $app->make( CurrencyService::class ),
                $app->make( Options::class )
            );
        });

        $this->app->singleton( ProcurementService::class, function( $app ) {
            return new ProcurementService(
                $app->make( ProviderService::class ),
                $app->make( UnitService::class ),
                $app->make( ProductService::class ),
                $app->make( CurrencyService::class )
            );
        });
    }
}