<?php

namespace App\Services;

use App\Classes\Hook;
use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegistersService
{
    public function openRegister( Register $register, $amount, $description )
    {
        if ( $register->status !== Register::STATUS_CLOSED ) {
            throw new NotAllowedException(
                sprintf(
                    __( 'Unable to open "%s", as it\'s not closed.' ),
                    $register->name
                )
            );
        }

        $register->status = Register::STATUS_OPENED;
        $register->used_by = Auth::id();
        $register->save();

        $registerHistory = new RegisterHistory;
        $registerHistory->register_id = $register->id;
        $registerHistory->action = RegisterHistory::ACTION_OPENING;
        $registerHistory->author = Auth::id();
        $registerHistory->description = $description;
        $registerHistory->balance_before = $register->balance;
        $registerHistory->value = $amount;
        $registerHistory->balance_after = ns()->currency->define( $register->balance )->additionateBy( $amount )->toFloat();
        $registerHistory->save();

        return [
            'status' => 'success',
            'message' => __( 'The register has been successfully opened' ),
            'data' => [
                'register' => $register,
                'history' => $registerHistory,
            ],
        ];
    }

    public function closeRegister( Register $register, $amount, $description )
    {
        if ( $register->status !== Register::STATUS_OPENED ) {
            throw new NotAllowedException(
                sprintf(
                    __( 'Unable to open "%s" *, as it\'s not opened.' ),
                    $register->name
                )
            );
        }

        if ( (float) $register->balance === (float) $amount ) {
            $diffType = 'unchanged';
        } else {
            $diffType = $register->balance < (float) $amount ? 'positive' : 'negative';
        }

        $registerHistory = new RegisterHistory;
        $registerHistory->register_id = $register->id;
        $registerHistory->action = RegisterHistory::ACTION_CLOSING;
        $registerHistory->transaction_type = $diffType;
        $registerHistory->balance_after = ns()->currency->define( $register->balance )->subtractBy( $amount )->toFloat();
        $registerHistory->value = ns()->currency->define( $amount )->toFloat();
        $registerHistory->balance_before = $register->balance;
        $registerHistory->author = Auth::id();
        $registerHistory->description = $description;
        $registerHistory->save();

        $register->status = Register::STATUS_CLOSED;
        $register->used_by = null;
        $register->balance = 0;
        $register->save();

        return [
            'status' => 'success',
            'message' => __( 'The register has been successfully closed' ),
            'data' => [
                'register' => $register,
                'history' => $registerHistory,
            ],
        ];
    }

    public function cashIng( Register $register, float $amount, mixed $transaction_account_id, ?string $description ): array
    {
        if ( $register->status !== Register::STATUS_OPENED ) {
            throw new NotAllowedException(
                sprintf(
                    __( 'Unable to cashing on "%s" *, as it\'s not opened.' ),
                    $register->name
                )
            );
        }

        if ( $amount <= 0 ) {
            throw new NotAllowedException( __( 'The provided amount is not allowed. The amount should be greater than "0". ' ) );
        }

        $registerHistory = new RegisterHistory;
        $registerHistory->register_id = $register->id;
        $registerHistory->action = RegisterHistory::ACTION_CASHING;
        $registerHistory->author = Auth::id();
        $registerHistory->description = $description;
        $registerHistory->balance_before = $register->balance;
        $registerHistory->transaction_account_id = $transaction_account_id;
        $registerHistory->value = ns()->currency->define( $amount )->toFloat();
        $registerHistory->balance_after = ns()->currency->define( $register->balance )->additionateBy( $amount )->toFloat();
        $registerHistory->save();

        return [
            'status' => 'success',
            'message' => __( 'The cash has successfully been stored' ),
            'data' => [
                'register' => $register,
                'history' => $registerHistory,
            ],
        ];
    }

    public function recordOrderPayment( OrderPayment $orderPayment )
    {
        $register = Register::find( $orderPayment->order->register_id );

        if ( ! $register instanceof Register ) {
            return [
                'status' => 'info',
                'message' => __( 'We can\'t attach a payment to a register as it\'s reference is not provided.' ),
            ];
        }

        $cashRegisterHistory = RegisterHistory::where( 'payment_id', $orderPayment->id )->first();

        /**
         * if the cash register history doesn't exists
         * then we'll create it for the payment.
         */
        if ( ! $cashRegisterHistory instanceof RegisterHistory ) {
            $cashRegisterHistory = new RegisterHistory;
            $cashRegisterHistory->register_id = $orderPayment->order->register_id;
            $cashRegisterHistory->payment_id = $orderPayment->id;
            $cashRegisterHistory->payment_type_id = $orderPayment->type->id;
            $cashRegisterHistory->order_id = $orderPayment->order_id;
            $cashRegisterHistory->action = RegisterHistory::ACTION_SALE;
            $cashRegisterHistory->author = $orderPayment->order->author;
            $cashRegisterHistory->balance_before = $register->balance;
            $cashRegisterHistory->value = ns()->currency->define( $orderPayment->value )->toFloat();
            $cashRegisterHistory->balance_after = ns()->currency->define( $register->balance )->additionateBy( $orderPayment->value )->toFloat();
            $cashRegisterHistory->save();

            return [
                'status' => 'success',
                'message' => __( 'The cash register history has been successfully updated' ),
                'data' => [
                    'register' => $register,
                    'history' => $cashRegisterHistory,
                ],
            ];
        }

        return [
            'status' => 'success',
            'message' => __( 'The cash register history has already been recorded' ),
            'data' => [
                'register' => $register,
                'history' => $cashRegisterHistory,
            ],
        ];
    }

    /**
     * @deprecated
     */
    public function saleDelete( Register $register, float $amount, string $description ): array
    {
        if ( $register->balance - $amount < 0 ) {
            throw new NotAllowedException(
                sprintf(
                    __( 'Not enough fund to delete a sale from "%s". If funds were cashed-out or disbursed, consider adding some cash (%s) to the register.' ),
                    $register->name,
                    trim( (string) ns()->currency->define( $amount ) )
                )
            );
        }

        $registerHistory = new RegisterHistory;
        $registerHistory->register_id = $register->id;
        $registerHistory->action = RegisterHistory::ACTION_DELETE;
        $registerHistory->author = Auth::id();
        $registerHistory->description = $description;
        $registerHistory->balance_before = $register->balance;
        $registerHistory->value = ns()->currency->define( $amount )->toFloat();
        $registerHistory->balance_after = ns()->currency->define( $register->balance )->subtractBy( $amount )->toFloat();
        $registerHistory->save();

        return [
            'status' => 'success',
            'message' => __( 'The cash has successfully been stored' ),
            'data' => [
                'register' => $register,
                'history' => $registerHistory,
            ],
        ];
    }

    public function cashOut( Register $register, float $amount, mixed $transaction_account_id, ?string $description ): array
    {
        if ( $register->status !== Register::STATUS_OPENED ) {
            throw new NotAllowedException(
                sprintf(
                    __( 'Unable to cashout on "%s", as it\'s not opened.' ),
                    $register->name
                )
            );
        }

        if ( $register->balance - $amount < 0 ) {
            throw new NotAllowedException(
                sprintf(
                    __( 'Not enough fund to cash out.' ),
                    $register->name
                )
            );
        }

        if ( $amount <= 0 ) {
            throw new NotAllowedException( __( 'The provided amount is not allowed. The amount should be greater than "0". ' ) );
        }

        $registerHistory = new RegisterHistory;
        $registerHistory->register_id = $register->id;
        $registerHistory->transaction_account_id = $transaction_account_id;
        $registerHistory->action = RegisterHistory::ACTION_CASHOUT;
        $registerHistory->author = Auth::id();
        $registerHistory->description = $description;
        $registerHistory->balance_before = ns()->currency->define( $register->balance )->toFloat();
        $registerHistory->value = ns()->currency->define( $amount )->toFloat();
        $registerHistory->balance_after = ns()->currency->define( $register->balance )->subtractBy( $amount )->toFloat();
        $registerHistory->save();

        return [
            'status' => 'success',
            'message' => __( 'The cash has successfully been disbursed.' ),
            'data' => [
                'register' => $register,
                'history' => $registerHistory,
            ],
        ];
    }

    /**
     * Will update the cash register balance using the
     * register history model.
     */
    public function updateRegisterBalance( RegisterHistory $registerHistory )
    {
        $register = Register::find( $registerHistory->register_id );

        if ( $register instanceof Register && $register->status === Register::STATUS_OPENED ) {
            if ( in_array( $registerHistory->action, RegisterHistory::IN_ACTIONS ) ) {
                $register->balance += $registerHistory->value;
            } elseif ( in_array( $registerHistory->action, RegisterHistory::OUT_ACTIONS ) ) {
                $register->balance -= $registerHistory->value;
            }

            $register->save();
        }
    }

    /**
     * Will increase the register balance if it's assigned
     * to the right store
     *
     * @return void
     *
     * @deprecated
     */
    public function recordCashRegisterHistorySale( Order $order )
    {
        if ( $order->register_id !== null ) {
            $register = Register::find( $order->register_id );

            /**
             * The customer wallet shouldn't be counted as
             * a payment that goes into the cash register.
             */
            $allowedPaymentIdentifiers = Hook::filter( 'ns-registers-allowed-payments-type', [
                OrderPayment::PAYMENT_CASH,
            ] );

            $payments = $order->payments()
                ->with( 'type' )
                ->whereIn( 'identifier', $allowedPaymentIdentifiers )
                ->get();

            /**
             * We'll only track on that cash register
             * payment that was recorded on the current register
             */
            $registerHistories = $payments->map( function ( OrderPayment $payment ) use ( $order, $register ) {
                if ( in_array( $payment->identifier, [ OrderPayment::PAYMENT_CASH, OrderPayment::PAYMENT_BANK ] ) ) {
                    $action = RegisterHistory::ACTION_SALE;
                } elseif ( in_array( $payment->identifier, [ OrderPayment::PAYMENT_ACCOUNT ] ) ) {
                    $action = RegisterHistory::ACTION_ACCOUNT_PAY;
                }

                /**
                 * if a not valid action is provided, we'll skip
                 * the record.
                 */
                if ( $action === null ) {
                    return;
                }

                $isRecorded = RegisterHistory::where( 'order_id', $order->id )
                    ->where( 'payment_id', $payment->id )
                    ->where( 'register_id', $register->id )
                    ->where( 'payment_type_id', $payment->type->id )
                    ->where( 'order_id', $order->id )
                    ->where( 'action', $action )
                    ->first() instanceof RegisterHistory;

                /**
                 * if a similar transaction is not yet record
                 * then we can record that on the register history.
                 */
                if ( ! $isRecorded ) {
                    $registerHistory = new RegisterHistory;
                    $registerHistory->balance_before = $register->balance;
                    $registerHistory->value = ns()->currency->define( $payment->value )->toFloat();
                    $registerHistory->balance_after = ns()->currency->define( $register->balance )->additionateBy( $payment->value )->toFloat();
                    $registerHistory->register_id = $register->id;
                    $registerHistory->payment_id = $payment->id;
                    $registerHistory->payment_type_id = $payment->type->id;
                    $registerHistory->order_id = $order->id;
                    $registerHistory->action = $action;
                    $registerHistory->author = $order->author;
                    $registerHistory->save();

                    return $registerHistory;
                }

                return false;
            } )->filter();

            /**
             * if the order has a change, we'll pull a register history stored as change
             * otherwise we'll create it.
             *
             * @todo we're forced to write down this snippet as the payments doesn't
             * yet support change as a (negative) payment.
             */
            if ( $order->change > 0 ) {
                $lastRegisterHistory = $registerHistories->last();

                $registerHistoryChange = RegisterHistory::where( 'order_id', $order->id )
                    ->where( 'register_id', $register->id )
                    ->where( 'order_id', $order->id )
                    ->where( 'action', RegisterHistory::ACTION_CASH_CHANGE )
                    ->firstOrNew();

                /**
                 * @todo payment_type_id and payment_id are omitted
                 * as this doesn't result from the order payment records.
                 */
                $registerHistoryChange->balance_before = $lastRegisterHistory->balance_after ?? 0;
                $registerHistoryChange->value = ns()->currency->define( $order->change )->toFloat();
                $registerHistoryChange->balance_after = ns()->currency->define( $lastRegisterHistory->balance_after ?? 0 )->subtractBy( $order->change )->toFloat();
                $registerHistoryChange->register_id = $register->id;
                $registerHistoryChange->order_id = $order->id;
                $registerHistoryChange->action = RegisterHistory::ACTION_CASH_CHANGE;
                $registerHistoryChange->author = $order->author;
                $registerHistoryChange->save();
            }

            $register->refresh();
        }
    }

    /**
     * Listen to order created and
     * will update the cash register if any order
     * is marked as paid.
     *
     * @deprecated ?
     */
    public function createRegisterHistoryFromPaidOrder( Order $order ): void
    {
        /**
         * If the payment status changed from
         * supported payment status to a "Paid" status.
         */
        if ( $order->register_id !== null && $order->payment_status === Order::PAYMENT_PAID ) {
            $register = Register::find( $order->register_id );

            $registerHistory = new RegisterHistory;
            $registerHistory->balance_before = $register->balance;
            $registerHistory->value = $order->total;
            $registerHistory->balance_after = ns()->currency->define( $register->balance )->additionateBy( $order->total )->toFloat();
            $registerHistory->register_id = $order->register_id;
            $registerHistory->action = RegisterHistory::ACTION_SALE;
            $registerHistory->author = $order->author;
            $registerHistory->save();
        }
    }

    /**
     * returns human readable labels
     * for all register actions.
     */
    public function getActionLabel( string $label ): string
    {
        switch ( $label ) {
            case RegisterHistory::ACTION_CASHING:
                return __( 'Cash In' );
                break;
            case RegisterHistory::ACTION_CASHOUT:
                return __( 'Cash Out' );
                break;
            case RegisterHistory::ACTION_CASH_CHANGE:
                return __( 'Change On Cash' );
                break;
            case RegisterHistory::ACTION_ACCOUNT_CHANGE:
                return __( 'Change On Customer Account' );
                break;
            case RegisterHistory::ACTION_CLOSING:
                return __( 'Closing' );
                break;
            case RegisterHistory::ACTION_OPENING:
                return __( 'Opening' );
                break;
            case RegisterHistory::ACTION_REFUND:
                return __( 'Refund' );
                break;
            case RegisterHistory::ACTION_SALE:
                return __( 'Sale' );
                break;
            default:
                return $label;
                break;
        }
    }

    /**
     * Returns the register status for human
     */
    public function getRegisterStatusLabel( string $label ): string
    {
        switch ( $label ) {
            case Register::STATUS_CLOSED:
                return __( 'Closed' );
                break;
            case Register::STATUS_DISABLED:
                return __( 'Disabled' );
                break;
            case Register::STATUS_INUSE:
                return __( 'In Use' );
                break;
            case Register::STATUS_OPENED:
                return __( 'Opened' );
                break;
            default:
                return $label;
                break;
        }
    }

    /**
     * Update the register with various details.
     */
    public function getRegisterDetails( Register $register ): Register
    {
        $register->status_label = $this->getRegisterStatusLabel( $register->status );
        $register->opening_balance = 0;
        $register->total_sale_amount = 0;

        if ( $register->status === Register::STATUS_OPENED ) {
            $history = $register->history()
                ->where( 'action', RegisterHistory::ACTION_OPENING )
                ->orderBy( 'id', 'desc' )->first();

            $register->opening_balance = $history->value;

            $register->total_sale_amount = Order::paid()
                ->where( 'register_id', $register->id )
                ->where( 'created_at', '>=', $history->created_at )
                ->sum( 'total' );
        }

        return $register;
    }

    private function diffInTime( $start, $end )
    {
        $startTime = Carbon::parse( $start );
        $endTime = Carbon::parse( $end );

        // Calculate the difference in total minutes
        $totalMinutes = $endTime->diffInMinutes( $startTime );

        // Calculate hours and minutes
        $hours = intdiv( $totalMinutes, 60 );
        $minutes = $totalMinutes % 60;

        // Format the result
        $formattedTime = sprintf( '%d:%02d', $hours, $minutes );

        return $formattedTime;
    }

    public function getZReport( Register $register )
    {
        $opening = RegisterHistory::where( 'register_id', $register->id )
            ->where( 'action', RegisterHistory::ACTION_OPENING )
            ->orderBy( 'id', 'desc' )
            ->first();

        $closing = RegisterHistory::where( 'register_id', $register->id )
            ->where( 'action', RegisterHistory::ACTION_CLOSING )
            ->where( 'id', '>', $opening->id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $histories = RegisterHistory::where( 'register_id', $register->id )
            ->where( 'created_at', '>=', $opening->created_at )
            ->orderBy( 'id', 'desc' )
            ->get();

        $orders = Order::paid()
            ->with( 'products' )
            ->where( 'register_id', $register->id )
            ->whereBetween( 'created_at', [ $opening->created_at, $closing->created_at ?? now()->toDateTimeString() ] )
            ->get();

        $payments = OrderPayment::whereIn( 'order_id', $orders->pluck( 'id' ) )
            ->select( 'nexopos_payments_types.identifier', DB::raw( 'SUM(value) as total_amount' ), 'label' )
            ->groupBy( [ 'identifier', 'label' ] )
            ->join( 'nexopos_payments_types', 'nexopos_payments_types.identifier', '=', 'nexopos_orders_payments.identifier' )
            ->get();

        $totalCashPayment = OrderPayment::whereIn( 'order_id', $orders->pluck( 'id' ) )
            ->where( 'identifier', OrderPayment::PAYMENT_CASH )
            ->sum( 'value' );

        $totalChange = $orders->sum( 'change' );
        $cashOnHand = ns()->currency->define( $opening->value )
            ->additionateBy( $totalCashPayment )
            ->subtractBy( $totalChange )
            ->toFloat();

        $openedOn = ns()->date->getFormatted( $opening->created_at );
        $closedOn = $closing ? ns()->date->getFormatted( $closing->created_at ) : __( 'Session Ongoing' );

        $openingBalance = ns()->currency->define( $opening->value );
        $closingBalance = ns()->currency->define( $closing->value ?? 0 );

        $rawTotalSales = $orders->sum( 'total' );
        $rawTotalShippings = $orders->sum( 'shipping' );
        $rawTotalDiscounts = $orders->sum( 'discount' );
        $rawTotalGrossSales = $orders->sum( 'net_total' );
        $rawTotalTaxes = $orders->sum( 'tax_value' );

        $totalDiscounts = ns()->currency->define( $rawTotalDiscounts );
        $totalSales = ns()->currency->define( $rawTotalSales );
        $totalGrossSales = ns()->currency->define( $rawTotalGrossSales );
        $totalShippings = ns()->currency->define( $rawTotalShippings );
        $totalTaxes = ns()->currency->define( $rawTotalTaxes );

        $sessionDuration = $this->diffInTime(
            $closing->created_at ?? now()->toDateTimeString(),
            $opening->created_at,
        );

        $difference = ns()->currency->define( $closing->value ?? 0 )
            ->subtractBy(
                ns()->currency
                    ->define( $opening->value )
                    ->additionateBy( $rawTotalSales )
                    ->additionateBy( $rawTotalShippings )
                    ->subtractBy( $rawTotalDiscounts )
                    ->toFloat()
            )
            ->format();

        $categories = [];
        $products = [];

        $orders->each( function ( $order ) use ( &$categories, &$products ) {
            return $order->products->each( function ( $item ) use ( &$categories, &$products ) {
                if ( ! isset( $categories[ $item->product->category->name ] ) ) {
                    $categories[ $item->product->category->id ] = [
                        'name' => $item->product->category->name,
                        'quantity' => 0,
                    ];
                }

                $categories[ $item->product->category->id ][ 'quantity' ] += $item->quantity;

                $productId = $item->product->id;

                if ( ! isset( $products[$productId] ) ) {
                    $products[$productId] = [
                        'name' => $item->product->name,
                        'total_price' => 0,
                        'quantity' => 0,
                        'tax_value' => 0,
                        'discount' => 0,
                    ];
                }

                $products[$productId]['total_price'] += $item->total_price;
                $products[$productId]['quantity'] += $item->quantity;
                $products[$productId]['tax_value'] += $item->tax_value;
                $products[$productId]['discount'] += $item->discount;
            } );
        } );

        $user = User::find( $opening->author );
        $cashier = $user->first_name . ' ' . $user->last_name . '(' . $user->username . ')';

        return (object) compact(
            'register',
            'opening',
            'closing',
            'openedOn',
            'closedOn',
            'histories',
            'orders',
            'openingBalance',
            'closingBalance',
            'difference',
            'totalGrossSales',
            'totalDiscounts',
            'totalShippings',
            'totalTaxes',
            'totalSales',
            'categories',
            'cashier',
            'sessionDuration',
            'payments',
            'cashOnHand',
            'products'
        );
    }
}
