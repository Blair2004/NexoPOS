<?php

namespace App\Providers;

use App\Classes\Hook;
use App\Events\ModulesBootedEvent;
use App\Events\ModulesLoadedEvent;
use App\Models\Order;
use App\Models\Permission;
use App\Services\AuthService;
use App\Services\BarcodeService;
use App\Services\CashRegistersService;
use App\Services\CoreService;
use App\Services\CrudService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use App\Services\DateService;
use App\Services\DemoService;
use App\Services\ExpenseService;
use App\Services\MediaService;
use App\Services\UpdateService;
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
use App\Services\NotificationService;
use App\Services\ReportService;
use App\Services\ResetService;
use App\Services\WebSocketService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
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

        $this->app->singleton( UpdateService::class, function(){
            return new UpdateService();
        });

        $this->app->singleton( DemoService::class, function(){
            return new DemoService(
                app()->make( ProductCategoryService::class ),
                app()->make( ProductService::class ),
                app()->make( ProcurementService::class ),
                app()->make( OrdersService::class )
            );
        });

        // save Singleton for options
        $this->app->singleton( DateService::class, function(){
            $options    =   app()->make( Options::class );
            $timeZone   =   $options->get( 'ns_datetime_timezone', 'Europe/London' );
            return new DateService( 'now', $timeZone );
        });

        // save Singleton for options
        $this->app->singleton( AuthService::class, function(){
            return new AuthService();
        });
        
        // save Singleton for options
        $this->app->singleton( UserOptions::class, function(){
            return new UserOptions( Auth::id() );
        });

        $this->app->singleton( CashRegistersService::class, function(){
            return new CashRegistersService();
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

        $this->app->singleton( BarcodeService::class, function() {
            return new BarcodeService;
        });

        $this->app->singleton( ResetService::class, function() {
            return new ResetService;
        });

        $this->app->singleton( ReportService::class, function() {
            return new ReportService(
                app()->make( DateService::class )
            );
        });

        $this->app->singleton( CoreService::class, function() {
            return new CoreService(
                app()->make( CurrencyService::class ),
                app()->make( UpdateService::class ),
                app()->make( DateService::class ),
                app()->make( OrdersService::class ),
                app()->make( NotificationService::class ),
                app()->make( ProcurementService::class ),
                app()->make( Options::class )
            );
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
                    'decimal_precision'     =>  $options->get( 'ns_currency_precision', 0 ),
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
                $app->make( UnitService::class ),
                $app->make( BarcodeService::class ),
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
            return new ExpenseService(
                app()->make( DateService::class )
            );
        });

        $this->app->singleton( WebSocketService::class, function( $app ) {
            return new WebSocketService();
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
                $app->make( ReportService::class ),
            );
        });

        $this->app->singleton( ProcurementService::class, function( $app ) {
            return new ProcurementService(
                $app->make( ProviderService::class ),
                $app->make( UnitService::class ),
                $app->make( ProductService::class ),
                $app->make( CurrencyService::class ),
                $app->make( DateService::class ),
                $app->make( BarcodeService::class ),
            );
        });

        /**
         * When the module has started, 
         * we can load the configuration.
         */
        Event::listen( function( ModulesBootedEvent $event ) {
            $this->loadConfiguration();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        /**
         * let's create a default sqlite
         * database. This file is not tracked by Git.
         */
        if( ! is_file( database_path( 'database.sqlite' ) ) ) {
            file_put_contents( database_path( 'database.sqlite' ), '' );
        }
    }

    /**
     * will trigger when the module
     * are fully loaded to ensure
     * they can modify the defined values.
     */
    protected function loadConfiguration()
    {
        config([ 'nexopos.orders.statuses'      => [
            Order::PAYMENT_HOLD                 =>  __( 'Hold' ),
            Order::PAYMENT_UNPAID               =>  __( 'Unpaid' ),
            Order::PAYMENT_PARTIALLY            =>  __( 'Partially Paid' ),
            Order::PAYMENT_PAID                 =>  __( 'Paid' ),
            Order::PAYMENT_VOID                 =>  __( 'Voided' ),
            Order::PAYMENT_REFUNDED             =>  __( 'Refunded' ),
            Order::PAYMENT_PARTIALLY_REFUNDED   =>  __( 'Partially Refunded' ),
            Order::PAYMENT_DUE                  =>  __( 'Due' ),
            Order::PAYMENT_PARTIALLY_DUE        =>  __( 'Partially Due' ),
        ]]);

        config([ 'nexopos.orders.types'         =>  Hook::filter( 'ns-orders-types', [
            'takeaway'          =>  [
                'identifier'    =>  'takeaway',
                'label'         =>  __( 'Take Away' ),
                'icon'          =>  '/images/groceries.png',
                'selected'      =>  false
            ], 
            'delivery'          =>  [
                'identifier'    =>  'delivery',
                'label'         =>  __( 'Delivery' ),
                'icon'          =>  '/images/delivery.png',
                'selected'      =>  false
            ]
        ])]);

        config([ 
            'nexopos.orders.types-labels' =>   collect( config( 'nexopos.orders.types' ) )
                ->mapWithKeys( fn( $type ) => [ $type[ 'identifier' ] => $type[ 'label' ] ])
                ->toArray()
        ]);
    }
}
