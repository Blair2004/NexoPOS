<?php

namespace App\Console\Commands;

use App\Models\OrderProduct;
use App\Services\DoctorService;
use App\Services\ProductService;
use Illuminate\Console\Command;

class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:doctor
        {--clear-modules-temp}
        {--fix-roles} 
        {--fix-users-attributes} 
        {--fix-orders-products} 
        {--fix-customers}
        {--fix-domains}
        {--fix-orphan-orders-products}
        {--fix-transactions-orders}
        {--set-unit-visibility=}
        {--products=}
        {--fix-duplicate-options}
        {--fix-products-cogs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will perform various tasks to fix issues on NexoPOS.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $doctorService = new DoctorService( $this );

        if ( $this->option( 'fix-roles' ) ) {
            $doctorService->restoreRoles();

            return $this->info( 'The roles where correctly restored.' );
        }

        if ( $this->option( 'fix-users-attributes' ) ) {
            $doctorService->createUserAttribute();

            return $this->info( 'The users attributes were fixed.' );
        }

        if ( $this->option( 'fix-duplicate-options' ) ) {
            $doctorService->fixDuplicateOptions();

            return $this->info( 'The duplicated options were cleared.' );
        }

        if ( $this->option( 'fix-customers' ) ) {
            $doctorService->fixCustomers();

            return $this->info( 'The customers were fixed.' );
        }

        if ( $this->option( 'fix-domains' ) ) {
            $doctorService->fixDomains();

            return $this->info( 'The domain is correctly configured.' );
        }

        if ( $this->option( 'fix-orphan-orders-products' ) ) {
            return $this->info( $doctorService->fixOrphanOrderProducts() );
        }

        if ( $this->option( 'fix-transactions-orders' ) ) {
            return $doctorService->fixTransactionsOrders();
        }

        if ( $this->option( 'fix-products-cogs' ) ) {
            return $doctorService->fixProductsCogs();
        }

        if ( $this->option( 'clear-modules-temp' ) ) {
            return $doctorService->clearTemporaryFiles();
        }

        if ( $this->option( 'set-unit-visibility' ) ) {
            return $doctorService->setUnitVisibility(
                products: $this->option( 'products' ),
                visibility: $this->option( 'set-unit-visibility' )
            );
        }

        if ( $this->option( 'fix-orders-products' ) ) {
            $products = OrderProduct::where( 'total_purchase_price', 0 )->get();

            /**
             * @var ProductService
             */
            $productService = app()->make( ProductService::class );

            $this->withProgressBar( $products, function ( OrderProduct $orderProduct ) use ( $productService ) {
                $orderProduct->total_purchase_price = $productService->getLastPurchasePrice(
                    product: $orderProduct->product,
                    unit: $orderProduct->unit,
                ) * $orderProduct->quantity;
                $orderProduct->save();
            } );

            $this->newLine();

            $this->info( 'The products were successfully updated' );
        }
    }
}
