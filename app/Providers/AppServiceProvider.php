<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthService;
use App\Services\CoreService;
use App\Services\CrudService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use App\Services\DateService;
use App\Services\ExpenseService;
use App\Services\MediaService;
use App\Services\MenuService;
use App\Services\Options;
use App\Services\OrdersService;
use App\Services\ProcurementService;
use App\Services\ProductCategoryService;
use App\Services\ProductService;
use App\Services\ProviderService;
use App\Services\TaxService;
use App\Services\UnitService;
use App\Services\UserOptions;
use App\Services\Users;
use App\Services\Validation;
use App\Services\ModulesService;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        include_once( base_path() . '/app/Services/HelperFunctions.php' );

        // save Singleton for options
        $this->app->singleton( Options::class, function(){
            return new Options();
        });

        $this->app->singleton( MenuService::class, function(){
            return new MenuService();
        });

        // save Singleton for options
        $this->app->singleton( DateService::class, function(){
            $options    =   app()->make( Options::class );
            $timeZone   =   $options->get( 'app_timezone', 'Europe/London' );
            return new DateService( 'now', $timeZone, $options );
        });

        // save Singleton for options
        $this->app->singleton( AuthService::class, function(){
            return new AuthService();
        });
        
        // save Singleton for options
        $this->app->singleton( UserOptions::class, function(){
            return new UserOptions( Auth::id() );
        });

        // save Singleton for options
        $this->app->singleton( Users::class, function(){
            return new Users( 
                Auth::user()->role,
                Auth::user(),
                new Permission()
            );
        });

        // provide media manager
        $this->app->singleton( MediaService::class, function() {
            return new MediaService([
                'extensions'    =>  [ 'jpg', 'jpeg', 'png', 'gif', 'zip', 'docx', 'txt' ]
            ]);
        });
        
        $this->app->singleton( CrudService::class, function() {
            return new CrudService;
        });

        $this->app->singleton( ReportService::class, function() {
            return new ReportService(
                app()->make( DateService::class )
            );
        });

        $this->app->singleton( CoreService::class, function() {
            return new CoreService;
        });

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
                    'decimal_precision'     =>  $options->get( 'ns_currency_precision', 2 ),
                    'decimal_separator'     =>  $options->get( 'ns_currency_decimal_separator', ',' ),
                    'thousand_separator'    =>  $options->get( 'ns_currency_thousand_separator', '.' ),
                    'currency_position'     =>  $options->get( 'ns_currency_position', 'before' ),
                    'currency_symbol'       =>  $options->get( 'ns_currency_symbol' ),
                    'currency_iso'          =>  $options->get( 'ns_currency_iso' ),
                    'prefered_currency'     =>  $options->get( 'ns_currency_prefered' ),
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
                $app->make( Options::class ),
                $app->make( TaxService::class ),
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

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }
}
