<?php

namespace App\Services;

use App\Classes\Currency;
use App\Models\Customer;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerShippingAddress;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Models\TransactionHistory;
use App\Models\User;
use App\Models\UserAttribute;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorService
{
    public function __construct( protected Command $command )
    {
        // ...
    }

    public function createUserAttribute(): array
    {
        User::get()->each( function ( User $user ) {
            $this->createAttributeForUser( $user );
        } );

        return [
            'status' => 'success',
            'message' => __( 'The user attributes has been updated.' ),
        ];
    }

    public function createAttributeForUser( User $user )
    {
        if ( ! $user->attribute instanceof UserAttribute ) {
            $attribute = new UserAttribute;
            $attribute->user_id = $user->id;
            $attribute->language = ns()->option->get( 'ns_store_language', 'en' );
            $attribute->theme = ns()->option->get( 'ns_default_theme', 'dark' );
            $attribute->save();
        }
    }

    public function fixProductsCogs()
    {
        $products = ProductUnitQuantity::get( [ 'unit_id', 'product_id', 'cogs' ] );

        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );

        $this->command->withProgressBar( $products, function ( ProductUnitQuantity $productUnitQuantity ) use ( $productService ) {
            $productService->computeCogs( productUnitQuantity: $productUnitQuantity );

            $allProducts = OrderProduct::where( 'product_id', $productUnitQuantity->product_id )
                ->where( 'unit_id', $productUnitQuantity->unit_id )
                ->get();

            $allProducts->each( function ( $orderProduct ) use ( $productUnitQuantity ) {
                $orderProduct->total_purchase_price = Currency::define( $productUnitQuantity->cogs )->multipliedBy( $orderProduct->quantity )->toFloat();
                $orderProduct->save();
            } );
        } );

        $this->command->newLine();
        $this->command->line( __( 'All products cogs were computed' ) );
    }

    /**
     * Will restore created roles
     */
    public function restoreRoles()
    {
        $rolesLabels = [
            Role::ADMIN => [
                'name' => __( 'Administrator' ),
            ],
            Role::STOREADMIN => [
                'name' => __( 'Store Administrator' ),
            ],
            Role::STORECASHIER => [
                'name' => __( 'Store Cashier' ),
            ],
            Role::USER => [
                'name' => __( 'User' ),
            ],
        ];

        foreach ( array_keys( $rolesLabels ) as $roleNamespace ) {
            $role = Role::where( 'namespace', $roleNamespace )
                ->first();

            if ( ! $role instanceof Role ) {
                Role::where( 'name', $rolesLabels[ $roleNamespace ][ 'name' ] )->delete();

                $role = new Role;
                $role->namespace = $roleNamespace;
                $role->name = $rolesLabels[ $roleNamespace ][ 'name' ];
                $role->locked = true;
                $role->save();
            }
        }
    }

    public function fixDuplicateOptions()
    {
        $options = Option::get();
        $options->each( function ( $option ) {
            try {
                $option->refresh();
                if ( $option instanceof Option ) {
                    Option::where( 'key', $option->key )
                        ->where( 'id', '<>', $option->id )
                        ->delete();
                }
            } catch ( Exception $exception ) {
                // the option might be deleted, let's skip that.
            }
        } );
    }

    public function fixOrphanOrderProducts()
    {
        $orderIds = Order::get( 'id' );

        $query = OrderProduct::whereNotIn( 'order_id', $orderIds );
        $total = $query->count();
        $query->delete();

        return sprintf( __( '%s products were freed' ), $total );
    }

    /**
     * useful to configure
     * session domain and sanctum stateful domains
     *
     * @return void
     */
    public function fixDomains()
    {
        /**
         * Set version to close setup
         */
        $domain = Str::replaceFirst( 'http://', '', url( '/' ) );
        $domain = Str::replaceFirst( 'https://', '', $domain );
        $withoutPort = explode( ':', $domain )[0];

        if ( ! env( 'SESSION_DOMAIN', false ) ) {
            ns()->envEditor->set( 'SESSION_DOMAIN', Str::replaceFirst( 'http://', '', $withoutPort ) );
        }

        if ( ! env( 'SANCTUM_STATEFUL_DOMAINS', false ) ) {
            ns()->envEditor->set( 'SANCTUM_STATEFUL_DOMAINS', collect( [ $domain, 'localhost', '127.0.0.1' ] )->unique()->join( ',' ) );
        }
    }

    /**
     * clear current cash flow and recompute
     * them using the current information.
     */
    public function fixTransactionsOrders()
    {
        /**
         * @var TransactionService $transactionService
         */
        $transactionService = app()->make( TransactionService::class );

        TransactionHistory::where( 'order_id', '>', 0 )->delete();
        TransactionHistory::where( 'order_refund_id', '>', 0 )->delete();

        /**
         * Step 1: Recompute from order sales
         */
        $orders = Order::paymentStatus( Order::PAYMENT_PAID )->get();

        $this->command->info( __( 'Restoring cash flow from paid orders...' ) );

        $this->command->withProgressBar( $orders, function ( $order ) {
            /**
             * @todo create transaction from order
             */
        } );

        $this->command->newLine();

        /**
         * Step 2: Recompute from refund
         */
        $this->command->info( __( 'Restoring cash flow from refunded orders...' ) );

        $orders = Order::paymentStatusIn( [
            Order::PAYMENT_REFUNDED,
            Order::PAYMENT_PARTIALLY_REFUNDED,
        ] )->get();

        $this->command->withProgressBar( $orders, function ( $order ) {
            $order->refundedProducts()->with( 'orderProduct' )->get()->each( function ( $orderRefundedProduct ) {
                // @todo create transaction from refund
            } );
        } );

        $this->command->newLine();
    }

    public function clearTemporaryFiles()
    {
        $directories = Storage::disk( 'ns-modules-temp' )->directories();
        $deleted = collect( $directories )->filter( fn( $directory ) => Storage::disk( 'ns-modules-temp' )->deleteDirectory( $directory ) );

        $this->command->info( sprintf(
            __( '%s on %s directories were deleted.' ),
            count( $directories ),
            $deleted->count()
        ) );

        $files = Storage::disk( 'ns-modules-temp' )->files();
        $deleted = collect( $files )->filter( fn( $file ) => Storage::disk( 'ns-modules-temp' )->delete( $file ) );

        $this->command->info( sprintf(
            __( '%s on %s files were deleted.' ),
            count( $files ),
            $deleted->count()
        ) );
    }

    public function fixCustomers()
    {
        $this->command
            ->withProgressBar( Customer::with( [ 'billing', 'shipping' ] )->get(), function ( $customer ) {
                if ( ! $customer->billing instanceof CustomerBillingAddress ) {
                    $billing = new CustomerBillingAddress;
                    $billing->customer_id = $customer->id;
                    $billing->first_name = $customer->first_name;
                    $billing->last_name = $customer->last_name;
                    $billing->email = $customer->email;
                    $billing->phone = $customer->phone;
                    $billing->author = $customer->author;
                    $billing->save();
                }

                if ( ! $customer->shipping instanceof CustomerShippingAddress ) {
                    $shipping = new CustomerShippingAddress;
                    $shipping->customer_id = $customer->id;
                    $shipping->first_name = $customer->first_name;
                    $shipping->last_name = $customer->last_name;
                    $shipping->email = $customer->email;
                    $shipping->phone = $customer->phone;
                    $shipping->author = $customer->author;
                    $shipping->save();
                }
            } );
    }

    /**
     * Check if all symbolic links available in a directory are not broken
     * and delete the broken symbolic links
     */
    public function clearBrokenModuleLinks(): array
    {
        $dir = base_path( 'public/modules' );
        $files = scandir( $dir );
        $deleted = [];

        foreach ( $files as $file ) {
            if ( $file === '.' || $file === '..' ) {
                continue;
            }

            if ( is_link( $dir . '/' . $file ) ) {
                if ( ! file_exists( readlink( $dir . '/' . $file ) ) ) {
                    $deleted[] = $dir . '/' . $file;
                    unlink( $dir . '/' . $file );
                }
            }
        }

        return [
            'status' => 'sucess',
            'message' => sprintf(
                __( '%s link were deleted' ),
                count( $deleted )
            ),
        ];
    }

    public function setUnitVisibility( string $products, bool $visibility )
    {
        $products = explode( ',', $products );

        if ( $products[ 0 ] === 'all' ) {
            $products = ProductUnitQuantity::get()->pluck( 'product_id' );
        }

        $this->command->info( sprintf(
            __( '%s products will be updated' ),
            count( $products )
        ) );

        $this->command->withProgressBar( $products, function ( $product ) use ( $visibility ) {
            $product = ProductUnitQuantity::where( 'product_id', $product )->first();

            if ( $product instanceof ProductUnitQuantity ) {
                $product->visible = $visibility;
                $product->save();
            }
        } );
    }
}
