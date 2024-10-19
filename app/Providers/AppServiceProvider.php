<?php

namespace App\Providers;

use App\Classes\Hook;
use App\Events\ModulesBootedEvent;
use App\Models\Order;
use App\Models\OrderProductRefund;
use App\Services\BarcodeService;
use App\Services\CashRegistersService;
use App\Services\CoreService;
use App\Services\CrudService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use App\Services\DateService;
use App\Services\DemoService;
use App\Services\EnvEditor;
use App\Services\Helper;
use App\Services\MathService;
use App\Services\MediaService;
use App\Services\MenuService;
use App\Services\NotificationService;
use App\Services\Options;
use App\Services\OrdersService;
use App\Services\ProcurementService;
use App\Services\ProductCategoryService;
use App\Services\ProductService;
use App\Services\ProviderService;
use App\Services\ReportService;
use App\Services\ResetService;
use App\Services\SetupService;
use App\Services\TaxService;
use App\Services\TransactionService;
use App\Services\UnitService;
use App\Services\UpdateService;
use App\Services\UserOptions;
use App\Services\UsersService;
use App\Services\Validation;
use App\Services\WidgetService;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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
        include_once base_path() . '/app/Services/HelperFunctions.php';

        AliasLoader::getInstance()->alias( 'Hook', Hook::class );

        $this->app->singleton( Options::class, function () {
            return new Options;
        } );

        $this->app->singleton( MenuService::class, function () {
            return new MenuService;
        } );

        $this->app->singleton( UpdateService::class, function () {
            return new UpdateService;
        } );

        $this->app->bind( DemoService::class, function () {
            return new DemoService(
                app()->make( ProductCategoryService::class ),
                app()->make( ProductService::class ),
                app()->make( ProcurementService::class ),
                app()->make( OrdersService::class ),
                app()->make( SetupService::class )
            );
        } );

        // save Singleton for options
        $this->app->singleton( DateService::class, function () {
            $options = app()->make( Options::class );
            $timeZone = $options->get( 'ns_datetime_timezone', 'Europe/London' );

            config( ['app.timezone' => $timeZone ] );
            date_default_timezone_set( $timeZone );

            return new DateService( 'now', $timeZone );
        } );

        $this->app->singleton( EnvEditor::class, function () {
            return new EnvEditor( base_path( '.env' ) );
        } );

        // save Singleton for options
        $this->app->singleton( UserOptions::class, function () {
            return new UserOptions( Auth::id() );
        } );

        $this->app->singleton( CashRegistersService::class, function () {
            return new CashRegistersService;
        } );

        // save Singleton for options
        $this->app->singleton( UsersService::class, function () {
            return new UsersService;
        } );

        // provide media manager
        $this->app->singleton( MediaService::class, function () {
            return new MediaService(
                dateService: app()->make( DateService::class )
            );
        } );

        $this->app->singleton( CrudService::class, function () {
            return new CrudService;
        } );

        $this->app->singleton( BarcodeService::class, function () {
            return new BarcodeService;
        } );

        $this->app->singleton( ResetService::class, function () {
            return new ResetService;
        } );

        $this->app->bind( ReportService::class, function () {
            return new ReportService(
                app()->make( DateService::class ),
                app()->make( ProductService::class ),
            );
        } );

        $this->app->singleton( CoreService::class, function () {
            return new CoreService(
                app()->make( CurrencyService::class ),
                app()->make( UpdateService::class ),
                app()->make( DateService::class ),
                app()->make( OrdersService::class ),
                app()->make( NotificationService::class ),
                app()->make( ProcurementService::class ),
                app()->make( Options::class ),
                app()->make( MathService::class ),
                app()->make( EnvEditor::class ),
                app()->make( MediaService::class ),
            );
        } );

        $this->app->bind( ProductCategoryService::class, function ( $app ) {
            return new ProductCategoryService;
        } );

        $this->app->bind( TaxService::class, function ( $app ) {
            return new TaxService(
                $app->make( CurrencyService::class )
            );
        } );

        $this->app->bind( CurrencyService::class, function ( $app ) {
            $options = app()->make( Options::class );

            return new CurrencyService(
                0, [
                    'decimal_precision' => $options->get( 'ns_currency_precision', 0 ),
                    'decimal_separator' => $options->get( 'ns_currency_decimal_separator', ',' ),
                    'thousand_separator' => $options->get( 'ns_currency_thousand_separator', '.' ),
                    'currency_position' => $options->get( 'ns_currency_position', 'before' ),
                    'currency_symbol' => $options->get( 'ns_currency_symbol' ),
                    'currency_iso' => $options->get( 'ns_currency_iso' ),
                    'prefered_currency' => $options->get( 'ns_currency_prefered' ),
                ]
            );
        } );

        $this->app->bind( ProductService::class, function ( $app ) {
            return new ProductService(
                $app->make( ProductCategoryService::class ),
                $app->make( TaxService::class ),
                $app->make( CurrencyService::class ),
                $app->make( UnitService::class ),
                $app->make( BarcodeService::class ),
            );
        } );

        $this->app->singleton( Validation::class, function ( $app ) {
            return new Validation;
        } );

        $this->app->bind( UnitService::class, function ( $app ) {
            return new UnitService(
                $app->make( CurrencyService::class )
            );
        } );

        $this->app->singleton( ProviderService::class, function ( $app ) {
            return new ProviderService;
        } );

        $this->app->singleton( CustomerService::class, function ( $app ) {
            return new CustomerService;
        } );

        $this->app->bind( TransactionService::class, function ( $app ) {
            return new TransactionService(
                app()->make( DateService::class ),
            );
        } );

        $this->app->bind( OrdersService::class, function ( $app ) {
            return new OrdersService(
                customerService: $app->make( CustomerService::class ),
                productService: $app->make( ProductService::class ),
                unitService: $app->make( UnitService::class ),
                dateService: $app->make( DateService::class ),
                currencyService: $app->make( CurrencyService::class ),
                optionsService: $app->make( Options::class ),
                taxService: $app->make( TaxService::class ),
                reportService: $app->make( ReportService::class ),
                mathService: $app->make( MathService::class ),
            );
        } );

        $this->app->bind( ProcurementService::class, function ( $app ) {
            return new ProcurementService(
                $app->make( ProviderService::class ),
                $app->make( UnitService::class ),
                $app->make( ProductService::class ),
                $app->make( CurrencyService::class ),
                $app->make( DateService::class ),
                $app->make( BarcodeService::class ),
            );
        } );

        $this->app->singleton( WidgetService::class, function ( $app ) {
            return new WidgetService(
                $app->make( UsersService::class )
            );
        } );

        /**
         * When the module has started,
         * we can load the configuration.
         */
        Event::listen( function ( ModulesBootedEvent $event ) {
            $this->loadConfiguration();
        } );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /**
         * let's create a default sqlite
         * database. This file is not tracked by Git.
         */
        if ( ! is_file( database_path( 'database.sqlite' ) ) ) {
            file_put_contents( database_path( 'database.sqlite' ), '' );
        }

        if ( Helper::installed() ) {
            Schema::defaultStringLength( 191 );
        }

        /**
         * We'll register a directive
         * that will help module loading
         * their Vite assets
         */
        Blade::directive( 'moduleViteAssets', function ( $expression ) {
            $params = explode( ',', $expression );
            $fileName = trim( $params[0], "'" );
            $module = trim( $params[1], " '" );

            return "<?php echo ns()->moduleViteAssets( \"{$fileName}\", \"{$module}\" ); ?>";
        } );
    }

    /**
     * will trigger when the module
     * are fully loaded to ensure
     * they can modify the defined values.
     */
    protected function loadConfiguration()
    {
        config( [ 'nexopos.orders.statuses' => [
            Order::PAYMENT_HOLD => __( 'Hold' ),
            Order::PAYMENT_UNPAID => __( 'Unpaid' ),
            Order::PAYMENT_PARTIALLY => __( 'Partially Paid' ),
            Order::PAYMENT_PAID => __( 'Paid' ),
            Order::PAYMENT_VOID => __( 'Voided' ),
            Order::PAYMENT_REFUNDED => __( 'Refunded' ),
            Order::PAYMENT_PARTIALLY_REFUNDED => __( 'Partially Refunded' ),
            Order::PAYMENT_DUE => __( 'Due' ),
            Order::PAYMENT_PARTIALLY_DUE => __( 'Partially Due' ),
        ]] );

        config( [ 'nexopos.orders.types' => Hook::filter( 'ns-orders-types', [
            'takeaway' => [
                'identifier' => 'takeaway',
                'label' => __( 'Take Away' ),
                'icon' => '/images/groceries.png',
                'selected' => false,
            ],
            'delivery' => [
                'identifier' => 'delivery',
                'label' => __( 'Delivery' ),
                'icon' => '/images/delivery.png',
                'selected' => false,
            ],
        ] )] );

        config( [
            'nexopos.orders.types-labels' => collect( config( 'nexopos.orders.types' ) )
                ->mapWithKeys( fn( $type ) => [ $type[ 'identifier' ] => $type[ 'label' ] ] )
                ->toArray(),
        ] );

        config( [
            'nexopos.orders.products.refunds' => [
                OrderProductRefund::CONDITION_DAMAGED => __( 'Damaged' ),
                OrderProductRefund::CONDITION_UNSPOILED => __( 'Good Condition' ),
            ],
        ] );
    }
}
