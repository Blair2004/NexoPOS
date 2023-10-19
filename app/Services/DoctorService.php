<?php

namespace App\Services;

use App\Console\Commands\DoctorCommand;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerBillingAddress;
use App\Models\CustomerShippingAddress;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttribute;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class DoctorService
{
    public function __construct( protected Command $command)
    {
        // ...
    }

    public function createUserAttribute(): array
    {
        User::get()->each( function ( User $user ) {
            if ( ! $user->attribute instanceof UserAttribute ) {
                $attribute = new UserAttribute;
                $attribute->user_id = $user->id;
                $attribute->language = ns()->option->get( 'ns_store_language', 'en' );
                $attribute->theme = ns()->option->get( 'ns_default_theme', 'dark' );
                $attribute->save();
            }
        });

        return [
            'status' => 'success',
            'message' => __( 'The user attributes has been updated.' ),
        ];
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
        });
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
            DotenvEditor::load();
            DotenvEditor::setKey( 'SESSION_DOMAIN', Str::replaceFirst( 'http://', '', $withoutPort ) );
            DotenvEditor::save();
        }

        if ( ! env( 'SANCTUM_STATEFUL_DOMAINS', false ) ) {
            DotenvEditor::load();
            DotenvEditor::setKey( 'SANCTUM_STATEFUL_DOMAINS', collect([ $domain, 'localhost', '127.0.0.1' ])->unique()->join(',') );
            DotenvEditor::save();
        }
    }

    /**
     * clear current cash flow and recompute
     * them using the current information.
     */
    public function fixCashFlowOrders( DoctorCommand $command )
    {
        /**
         * @var ExpenseService $expenseService
         */
        $expenseService = app()->make( ExpenseService::class );

        CashFlow::where( 'order_id', '>', 0 )->delete();
        CashFlow::where( 'order_refund_id', '>', 0 )->delete();

        /**
         * Step 1: Recompute from order sales
         */
        $orders = Order::paymentStatus( Order::PAYMENT_PAID )->get();

        $command->info( __( 'Restoring cash flow from paid orders...' ) );

        $command->withProgressBar( $orders, function ( $order ) use ( $expenseService ) {
            $expenseService->handleCreatedOrder( $order );
        });

        $command->newLine();

        /**
         * Step 2: Recompute from refund
         */
        $command->info( __( 'Restoring cash flow from refunded orders...' ) );

        $orders = Order::paymentStatusIn([
            Order::PAYMENT_REFUNDED,
            Order::PAYMENT_PARTIALLY_REFUNDED,
        ])->get();

        $command->withProgressBar( $orders, function ( $order ) use ( $expenseService ) {
            $order->refundedProducts()->with( 'orderProduct' )->get()->each( function ( $orderRefundedProduct ) use ( $order, $expenseService ) {
                $expenseService->createExpenseFromRefund(
                    order: $order,
                    orderProductRefund: $orderRefundedProduct,
                    orderProduct: $orderRefundedProduct->orderProduct
                );
            });
        });

        $command->newLine();
    }

    public function fixCustomers()
    {
        $this->command
            ->withProgressBar( Customer::with([ 'billing', 'shipping' ])->get(), function ( $customer ) {
                if ( ! $customer->billing instanceof CustomerBillingAddress ) {
                    $billing = new CustomerBillingAddress;
                    $billing->customer_id = $customer->id;
                    $billing->name = $customer->name;
                    $billing->surname = $customer->surname;
                    $billing->email = $customer->email;
                    $billing->phone = $customer->phone;
                    $billing->author = $customer->author;
                    $billing->save();
                }

                if ( ! $customer->shipping instanceof CustomerShippingAddress ) {
                    $shipping = new CustomerShippingAddress;
                    $shipping->customer_id = $customer->id;
                    $shipping->name = $customer->name;
                    $shipping->surname = $customer->surname;
                    $shipping->email = $customer->email;
                    $shipping->phone = $customer->phone;
                    $shipping->author = $customer->author;
                    $shipping->save();
                }
            });
    }
}
