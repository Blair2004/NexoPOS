<?php

namespace App\Services;

use App\Classes\Hook;
use App\Events\TransactionAfterCreatedEvent;
use App\Events\TransactionAfterUpdatedEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Fields\DirectTransactionFields;
use App\Fields\EntityTransactionFields;
use App\Fields\ReccurringTransactionFields;
use App\Fields\ScheduledTransactionFields;
use App\Models\TransactionAccount;
use App\Models\TransactionHistory;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
use App\Models\Procurement;
use App\Models\RegisterHistory;
use App\Models\Role;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class TransactionService
{
    /**
     * @var DateService
     */
    protected $dateService;

    protected $accountTypes = [
        TransactionHistory::ACCOUNT_SALES => [ 'operation' => TransactionHistory::OPERATION_CREDIT, 'option' => 'ns_sales_cashflow_account' ],
        TransactionHistory::ACCOUNT_REFUNDS => [ 'operation' => TransactionHistory::OPERATION_DEBIT, 'option' => 'ns_sales_refunds_account' ],
        TransactionHistory::ACCOUNT_SPOILED => [ 'operation' => TransactionHistory::OPERATION_DEBIT, 'option' => 'ns_stock_return_spoiled_account' ],
        TransactionHistory::ACCOUNT_PROCUREMENTS => [ 'operation' => TransactionHistory::OPERATION_DEBIT, 'option' => 'ns_procurement_cashflow_account' ],
        TransactionHistory::ACCOUNT_CUSTOMER_CREDIT => [ 'operation' => TransactionHistory::OPERATION_CREDIT, 'option' => 'ns_customer_crediting_cashflow_account' ],
        TransactionHistory::ACCOUNT_CUSTOMER_DEBIT => [ 'operation' => TransactionHistory::OPERATION_DEBIT, 'option' => 'ns_customer_debitting_cashflow_account' ],
    ];

    public function __construct( DateService $dateService )
    {
        $this->dateService = $dateService;
    }

    public function create( $fields )
    {
        $transaction = new Transaction;

        foreach ( $fields as $field => $value ) {
            $transaction->$field = $value;
        }

        $transaction->author = Auth::id();
        $transaction->save();

        event( new TransactionAfterCreatedEvent( $transaction, request()->all() ) );

        return [
            'status' => 'success',
            'message' => __( 'The transaction has been successfully saved.' ),
            'data' => compact( 'transaction' ),
        ];
    }

    public function edit( $id, $fields )
    {
        $transaction = $this->get( $id );

        if ( $transaction instanceof Transaction ) {
            foreach ( $fields as $field => $value ) {
                $transaction->$field = $value;
            }

            $transaction->author = Auth::id();
            $transaction->save();

            event( new TransactionAfterUpdatedEvent( $transaction, request()->all() ) );

            return [
                'status' => 'success',
                'message' => __( 'The transaction has been successfully updated.' ),
                'data' => compact( 'transaction' ),
            ];
        }

        throw new NotFoundException( __( 'Unable to find the transaction using the provided identifier.' ) );
    }

    /**
     * get a specific transaction using
     * the provided id
     * @throws NotFoundException
     */
    public function get( int $id = null ): Collection|Transaction
    {
        if ( $id === null ) {
            return Transaction::get();
        }

        $transaction = Transaction::find( $id );

        if ( ! $transaction instanceof Transaction ) {
            throw new NotFoundException( __( 'Unable to find the requested transaction using the provided id.' ) );
        }

        return $transaction;
    }

    /**
     * Delete an transction using the
     * provided id
     *
     * @param int transction id
     * @return array
     */
    public function delete( $id )
    {
        $transaction = $this->get( $id );
        $transaction->delete();

        return [
            'status' => 'success',
            'message' => __( 'The transction has been correctly deleted.' ),
        ];
    }

    /**
     * Retreive a specific account type
     * or all account type
     */
    public function getTransactionAccountByID( int $id = null )
    {
        if ( $id !== null ) {
            $account = TransactionAccount::find( $id );
            if ( ! $account instanceof TransactionAccount ) {
                throw new NotFoundException( __( 'Unable to find the requested account type using the provided id.' ) );
            }

            return $account;
        }

        return TransactionAccount::get();
    }

    /**
     * Delete specific account type
     *
     * @todo must be implemented
     */
    public function deleteTransactionAccount( $id, $force = true )
    {
        $accountType = $this->getTransactionAccountByID( $id );

        if ( $accountType->transactions->count() > 0 && $force === false ) {
            throw new NotAllowedException( __( 'You cannot delete an account type that has transaction bound.' ) );
        }

        /**
         * if there is not transaction, it
         * won't be looped
         */
        $accountType->transactions->map( function( $transaction ) {
            $transaction->delete();
        });

        $accountType->delete();

        return [
            'status' => 'success',
            'message' => __( 'The account type has been deleted.' ),
        ];
    }

    /**
     * Delete a specific account
     * using the provided id, along with the transactions
     *
     * @param int id
     * @param bool force deleting
     * @return array|NotAllowedException
     */
    public function deleteCategory( $id, $force = false )
    {
        $accountType = $this->getTransactionAccountByID( $id );

        if ( $accountType->transactions->count() > 0 && $force === false ) {
            throw new NotAllowedException( __( 'You cannot delete an account which has transactions bound.' ) );
        }

        /**
         * if there is not transaction, it
         * won't be looped
         */
        $accountType->transactions->map( function( $transaction ) {
            $transaction->delete();
        });

        $accountType->delete();

        return [
            'status' => 'success',
            'message' => __( 'The transaction account has been deleted.' ),
        ];
    }

    /**
     * Get a specific transaction
     * account using the provided ID
     */
    public function getTransaction( int $id )
    {
        $accountType = TransactionAccount::with( 'transactions' )->find( $id );

        if ( ! $accountType instanceof TransactionAccount ) {
            throw new NotFoundException( __( 'Unable to find the transaction account using the provided ID.' ) );
        }

        return $accountType;
    }

    /**
     * Create an account using
     * the provided details
     *
     * @param array account detail
     * @return array status of the operation
     *
     * @deprecated
     */
    public function createCategory( $fields )
    {
        $account = new TransactionAccount;

        foreach ( $fields as $field => $value ) {
            $account->$field = $value;
        }

        $account->author = Auth::id();
        $account->save();

        return [
            'status' => 'success',
            'message' => __( 'The transaction account has been saved' ),
            'data' => compact( 'account' ),
        ];
    }

    /**
     * Creates an accounting account
     *
     * @param array $fields
     * @return array status
     */
    public function createAccount( $fields )
    {
        $account = new TransactionAccount;

        foreach ( $fields as $field => $value ) {
            $account->$field = $value;
        }

        $account->author = ns()->getValidAuthor();
        $account->save();

        return [
            'status' => 'success',
            'message' => __( 'The account has been created.' ),
            'data' => compact( 'account' ),
        ];
    }

    /**
     * Update specified expense
     * account using a provided ID
     */
    public function editTransactionAccount( int $id, array $fields ): array
    {
        $account = $this->getTransaction( $id );

        foreach ( $fields as $field => $value ) {
            $account->$field = $value;
        }

        $account->author = Auth::id();
        $account->save();

        return [
            'status' => 'success',
            'message' => __( 'The transaction account has been updated.' ),
            'data' => compact( 'account' ),
        ];
    }

    /**
     * Will trigger for not recurring transaction
     *
     * @param Transaction $transaction
     * @return void
     */
    public function triggerTransaction( $transaction )
    {
        $histories = $this->recordTransactionHistory( $transaction );

        /**
         * a non recurring transaction
         * once triggered should be disabled to
         * prevent futher execution on modification.
         */
        $transaction->active = false;
        $transaction->save();

        return compact( 'transaction', 'histories' );
    }

    public function getCategoryTransaction( $id )
    {
        $accountType = $this->getTransaction( $id );

        return $accountType->transactions;
    }

    public function recordTransactionHistory( $transaction )
    {
        if ( ! empty( $transaction->group_id  ) ) {
            return Role::find( $transaction->group_id )->users()->get()->map( function( $user ) use ( $transaction ) {
                if ( $transaction->account instanceof TransactionAccount ) {
                    $history = new TransactionHistory;
                    $history->value = $transaction->value;
                    $history->transaction_id = $transaction->id;
                    $history->operation = 'debit';
                    $history->author = $transaction->author;
                    $history->name = str_replace( '{user}', ucwords( $user->username ), $transaction->name );
                    $history->transaction_account_id = $transaction->account->id;
                    $history->save();

                    return $history;
                }

                return false;
            })->filter(); // only return valid history created
        } else {
            if ( $transaction->account instanceof TransactionAccount ) {
                $history = new TransactionHistory;
                $history->value = $transaction->value;
                $history->transaction_id = $transaction->id;
                $history->operation = $transaction->operation ?? 'debit'; // if the operation is not defined, by default is a "debit"
                $history->author = $transaction->author;
                $history->name = $transaction->name;
                $history->procurement_id = $transaction->procurement_id ?? 0; // if the cash flow is created from a procurement
                $history->order_id = $transaction->order_id ?? 0; // if the cash flow is created from a refund
                $history->order_refund_id = $transaction->order_refund_id ?? 0; // if the cash flow is created from a refund
                $history->order_product_id = $transaction->order_product_id ?? 0; // if the cash flow is created from a refund
                $history->order_refund_product_id = $transaction->order_refund_product_id ?? 0; // if the cash flow is created from a refund
                $history->register_history_id = $transaction->register_history_id ?? 0; // if the cash flow is created from a register transaction
                $history->customer_account_history_id = $transaction->customer_account_history_id ?? 0; // if the cash flow is created from a customer payment.
                $history->transaction_account_id = $transaction->account->id;
                $history->save();
    
                return collect([ $history ]);
            } else {
                throw new ModelNotFoundException( sprintf( 'The transaction account is not found.' ) );
            }
        }
    }

    /**
     * Process recorded transactions
     * and check whether they are supposed to be processed
     * on the current day.
     *
     * @return array of process results.
     */
    public function handleRecurringTransactions( Carbon $date = null )
    {
        if ( $date === null ) {
            $date = $this->dateService->copy();
        }

        $processStatus = Transaction::recurring()
            ->active()
            ->get()
            ->map( function( $transaction ) use ( $date ) {
                switch ( $transaction->occurrence ) {
                    case 'month_starts':
                        $transactionScheduledDate = $date->copy()->startOfMonth();
                        break;
                    case 'month_mid':
                        $transactionScheduledDate = $date->copy()->startOfMonth()->addDays(14);
                        break;
                    case 'month_ends':
                        $transactionScheduledDate = $date->copy()->endOfMonth();
                        break;
                    case 'x_before_month_ends':
                        $transactionScheduledDate = $date->copy()->endOfMonth()->subDays( $transaction->occurrence_value );
                        break;
                    case 'x_after_month_starts':
                        $transactionScheduledDate = $date->copy()->startOfMonth()->addDays( $transaction->occurrence_value );
                        break;
                    case 'on_specific_day':
                        $transactionScheduledDate = $date->copy();
                        $transactionScheduledDate->day = $transaction->occurrence_value;
                        break;
                }

                if ( isset( $transactionScheduledDate ) && $transactionScheduledDate instanceof Carbon ) {
                    /**
                     * Checks if the recurring transactions about to be saved has been
                     * already issued on the occuring day.
                     */
                    if ( $date->isSameDay( $transactionScheduledDate ) ) {
                        if ( ! $this->hadTransactionHistory( $transactionScheduledDate, $transaction ) ) {
                            $histories = $this->recordTransactionHistory( $transaction );

                            return [
                                'status' => 'success',
                                'data' => compact( 'transaction', 'histories' ),
                                'message' => sprintf( __( 'The transaction "%s" has been processed on day "%s".' ), $transaction->name, $date->toDateTimeString() ),
                            ];
                        }

                        return [
                            'status' => 'failed',
                            'message' => sprintf( __( 'The transaction "%s" has already been processed.' ), $transaction->name ),
                        ];
                    }
                }

                return [
                    'status' => 'failed',
                    'message' => sprintf( __( 'The transactions "%s" hasn\'t been proceesed, as it\'s out of date.' ), $transaction->name ),
                ];
            });

        $successFulProcesses = collect( $processStatus )->filter( fn( $process ) => $process[ 'status' ] === 'success' );

        return [
            'status' => 'success',
            'data' => $processStatus->toArray(),
            'message' => $successFulProcesses->count() === $processStatus->count() ?
                __( 'The process has been correctly executed and all transactions has been processed.' ) :
                    sprintf( __( 'The process has been executed with some failures. %s/%s process(es) has successed.' ), $successFulProcesses->count(), $processStatus->count() ),
        ];
    }

    /**
     * Check if an transaction has been executed during a day.
     * To prevent many recurring transactions to trigger multiple times
     * during a day.
     */
    public function hadTransactionHistory( $date, Transaction $transaction )
    {
        $history = TransactionHistory::where( 'transaction_id', $transaction->id )
            ->where( 'created_at', '>=', $date->startOfDay()->toDateTimeString() )
            ->where( 'created_at', '<=', $date->endOfDay()->toDateTimeString() )
            ->get();

        return $history instanceof TransactionHistory;
    }

    /**
     * Will record a transaction resulting from a paid procurement
     *
     * @param Procurement $procurement
     * @return void
     */
    public function handleProcurementTransaction( Procurement $procurement )
    {
        if (
            $procurement->payment_status === Procurement::PAYMENT_PAID &&
            $procurement->delivery_status === Procurement::STOCKED
        ) {
            $accountTypeCode = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_PROCUREMENTS );

            /**
             * this behave as a flash transaction
             * made only for recording an history.
             */
            $transaction = new Transaction;
            $transaction->value = $procurement->cost;
            $transaction->active = true;
            $transaction->author = $procurement->author;
            $transaction->procurement_id = $procurement->id;
            $transaction->name = sprintf( __( 'Procurement : %s' ), $procurement->name );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $accountTypeCode;
            $transaction->created_at = $procurement->created_at;
            $transaction->updated_at = $procurement->updated_at;

            $this->recordTransactionHistory( $transaction );
        }
    }

    /**
     * Will record a transaction for every refund performed
     *
     * @param OrderProduct $orderProduct
     * @return void
     */
    public function createTransactionFromRefund( Order $order, OrderProductRefund $orderProductRefund, OrderProduct $orderProduct )
    {
        $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_REFUNDS );

        /**
         * Every product refund produce a debit
         * operation on the system.
         */
        $transaction = new Transaction;
        $transaction->value = $orderProductRefund->total_price;
        $transaction->active = true;
        $transaction->operation = TransactionHistory::OPERATION_DEBIT;
        $transaction->author = $orderProductRefund->author;
        $transaction->order_id = $order->id;
        $transaction->order_product_id = $orderProduct->id;
        $transaction->order_refund_id = $orderProductRefund->order_refund_id;
        $transaction->order_refund_product_id = $orderProductRefund->id;
        $transaction->name = sprintf( __( 'Refunding : %s' ), $orderProduct->name );
        $transaction->id = 0; // this is not assigned to an existing transaction
        $transaction->account = $transactionAccount;

        $this->recordTransactionHistory( $transaction );

        if ( $orderProductRefund->condition === OrderProductRefund::CONDITION_DAMAGED ) {
            /**
             * Only if the product is damaged we should
             * consider saving that as a waste.
             */
            $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_SPOILED );

            $transaction = new Transaction;
            $transaction->value = $orderProductRefund->total_price;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_DEBIT;
            $transaction->author = $orderProductRefund->author;
            $transaction->order_id = $order->id;
            $transaction->order_product_id = $orderProduct->id;
            $transaction->order_refund_id = $orderProductRefund->order_refund_id;
            $transaction->order_refund_product_id = $orderProductRefund->id;
            $transaction->name = sprintf( __( 'Spoiled Good : %s' ), $orderProduct->name );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;

            $this->recordTransactionHistory( $transaction );
        }
    }

    /**
     * If the order has just been
     * created and the payment status is PAID
     * we'll store the total as a cash flow transaction.
     *
     * @param Order $order
     * @return void
     */
    public function handleCreatedOrder( Order $order )
    {
        if ( $order->payment_status === Order::PAYMENT_PAID ) {
            $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_SALES );

            $transaction = new Transaction;
            $transaction->value = $order->total;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_CREDIT;
            $transaction->author = $order->author;
            $transaction->order_id = $order->id;
            $transaction->name = sprintf( __( 'Sale : %s' ), $order->code );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $order->created_at;
            $transaction->updated_at = $order->updated_at;

            $this->recordTransactionHistory( $transaction );
        }
    }

    /**
     * Will pul the defined account
     * or will create a new one according to the settings
     *
     * @param string $accountSettingsName
     * @param array $defaults
     */
    public function getDefinedTransactionAccount( $accountSettingsName, $defaults ): TransactionAccount
    {
        $accountType = TransactionAccount::find( ns()->option->get( $accountSettingsName ) );

        if ( ! $accountType instanceof TransactionAccount ) {
            $result = $this->createAccount( $defaults );

            $accountType = (object) $result[ 'data' ][ 'account' ];

            /**
             * Will set the transaction as the default account transaction
             * account for subsequent transactions.
             */
            ns()->option->set( $accountSettingsName, $accountType->id );

            $accountType = TransactionAccount::find( ns()->option->get( $accountSettingsName ) );
        }

        return $accountType;
    }

    /**
     * Will compare order payment status
     * and if the previous and the new payment status are supported
     * the transaction will be record to the cash flow.
     */
    public function handlePaymentStatus( string $previous, string $new, Order $order )
    {
        if ( in_array( $previous, [
            Order::PAYMENT_HOLD,
            Order::PAYMENT_DUE,
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_PARTIALLY_DUE,
            Order::PAYMENT_UNPAID,
        ]) && in_array(
            $new, [
                Order::PAYMENT_PAID,
            ]
        )) {
            $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_SALES );

            $transaction = new Transaction;
            $transaction->value = $order->total;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_CREDIT;
            $transaction->author = $order->author;
            $transaction->order_id = $order->id;
            $transaction->name = sprintf( __( 'Sale : %s' ), $order->code );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;

            $this->recordTransactionHistory( $transaction );
        }
    }

    public function recomputeTransactionHistory( $rangeStarts = null, $rangeEnds = null )
    {
        /**
         * We'll register cash flow for complete orders
         */
        $this->processPaidOrders( $rangeStarts, $rangeEnds );
        $this->processCustomerAccountHistories( $rangeStarts, $rangeEnds );
        $this->processTransactions( $rangeStarts, $rangeEnds );
        $this->processProcurements( $rangeStarts, $rangeEnds );
        $this->processRecurringTransactions( $rangeStarts, $rangeEnds );
        $this->processRefundedOrders( $rangeStarts, $rangeEnds );
    }

    /**
     * Retreive the account configuration
     * using the account type
     *
     * @param string $type
     */
    public function getTransactionAccountByCode( $type ): TransactionAccount
    {
        $account = $this->accountTypes[ $type ] ?? false;

        if ( ! empty( $account ) ) {
            /**
             * This will define the label
             */
            switch ( $type ) {
                case TransactionHistory::ACCOUNT_CUSTOMER_CREDIT: $label = __( 'Customer Credit Account' );
                    break;
                case TransactionHistory::ACCOUNT_CUSTOMER_DEBIT: $label = __( 'Customer Debit Account' );
                    break;
                case TransactionHistory::ACCOUNT_PROCUREMENTS: $label = __( 'Procurements Account' );
                    break;
                case TransactionHistory::ACCOUNT_REFUNDS: $label = __( 'Sales Refunds Account' );
                    break;
                case TransactionHistory::ACCOUNT_REGISTER_CASHIN: $label = __( 'Register Cash-In Account' );
                    break;
                case TransactionHistory::ACCOUNT_REGISTER_CASHOUT: $label = __( 'Register Cash-Out Account' );
                    break;
                case TransactionHistory::ACCOUNT_SALES: $label = __( 'Sales Account' );
                    break;
                case TransactionHistory::ACCOUNT_SPOILED: $label = __( 'Spoiled Goods Account' );
                    break;
            }

            return $this->getDefinedTransactionAccount( $account[ 'option' ], [
                'name' => $label,
                'operation' => $account[ 'operation' ],
                'account' => $type,
            ]);
        }

        throw new NotFoundException( sprintf(
            __( 'Not found account type: %s' ),
            $type
        ) );
    }

    /**
     * Will process refunded orders
     *
     * @param string $rangeStart
     * @param string $rangeEnds
     * @return void
     */
    public function processRefundedOrders( $rangeStarts, $rangeEnds )
    {
        $orders = Order::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->paymentStatus( Order::PAYMENT_REFUNDED )
            ->get();

        $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_REFUNDS );

        $orders->each( function( $order ) use ( $transactionAccount ) {
            $transaction = new Transaction;
            $transaction->value = $order->total;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_DEBIT;
            $transaction->author = $order->author;
            $transaction->customer_account_history_id = $order->id;
            $transaction->name = sprintf( __( 'Refund : %s' ), $order->code );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $order->created_at;
            $transaction->updated_at = $order->updated_at;

            $this->recordTransactionHistory( $transaction );
        });
    }

    /**
     * Will process paid orders
     *
     * @param string $rangeStart
     * @param string $rangeEnds
     * @return void
     */
    public function processPaidOrders( $rangeStart, $rangeEnds )
    {
        $orders = Order::where( 'created_at', '>=', $rangeStart )
            ->with( 'customer' )
            ->where( 'created_at', '<=', $rangeEnds )
            ->paymentStatus( Order::PAYMENT_PAID )
            ->get();

        $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_SALES );

        Customer::where( 'id', '>', 0 )->update([ 'purchases_amount' => 0 ]);

        $orders->each( function( $order ) use ( $transactionAccount ) {
            $transaction = new Transaction;
            $transaction->value = $order->total;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_CREDIT;
            $transaction->author = $order->author;
            $transaction->customer_account_history_id = $order->id;
            $transaction->name = sprintf( __( 'Sale : %s' ), $order->code );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $order->created_at;
            $transaction->updated_at = $order->updated_at;

            $customer = Customer::find( $order->customer_id );

            if ( $customer instanceof Customer ) {
                $customer->purchases_amount += $order->total;
                $customer->save();
            }

            $this->recordTransactionHistory( $transaction );
        });
    }

    /**
     * Will process the customer histories
     *
     * @return void
     */
    public function processCustomerAccountHistories( $rangeStarts, $rangeEnds )
    {
        $histories = CustomerAccountHistory::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->get();

        $histories->each( function( $history ) {
            $this->handleCustomerCredit( $history );
        });
    }

    /**
     * Will create an transaction for each created procurement
     *
     * @return void
     */
    public function processProcurements( $rangeStarts, $rangeEnds )
    {
        Procurement::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->get()->each( function( $procurement ) {
                $this->handleProcurementTransaction( $procurement );
            });
    }

    /**
     * Will trigger not recurring transactions
     *
     * @return void
     */
    public function processTransactions( $rangeStarts, $rangeEnds )
    {
        Transaction::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->notRecurring()
            ->get()
            ->each( function( $transaction ) {
                $this->triggerTransaction( $transaction );
            });
    }

    public function processRecurringTransactions( $rangeStart, $rangeEnds )
    {
        $startDate = Carbon::parse( $rangeStart );
        $endDate = Carbon::parse( $rangeEnds );

        if ( $startDate->lessThan( $endDate ) && $startDate->diffInDays( $endDate ) >= 1 ) {
            while ( $startDate->isSameDay() ) {
                ns()->date = $startDate;

                $this->handleRecurringTransactions( $startDate );

                $startDate->addDay();
            }
        }
    }

    /**
     * Will add customer credit operation
     * to the cash flow history
     *
     * @param CustomerAccountHistory $customerHistory
     * @return void
     */
    public function handleCustomerCredit( CustomerAccountHistory $customerHistory )
    {
        if ( in_array( $customerHistory->operation, [
            CustomerAccountHistory::OPERATION_ADD,
            CustomerAccountHistory::OPERATION_REFUND,
        ]) ) {
            $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_CUSTOMER_CREDIT );

            $transaction = new Transaction;
            $transaction->value = $customerHistory->amount;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_CREDIT;
            $transaction->author = $customerHistory->author;
            $transaction->customer_account_history_id = $customerHistory->id;
            $transaction->name = sprintf( __( 'Customer Account Crediting : %s' ), $customerHistory->customer->name );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $customerHistory->created_at;
            $transaction->updated_at = $customerHistory->updated_at;

            $this->recordTransactionHistory( $transaction );
        } elseif ( in_array(
            $customerHistory->operation, [
                CustomerAccountHistory::OPERATION_PAYMENT,
            ]
        ) ) {
            $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_CUSTOMER_DEBIT );

            $transaction = new Transaction;
            $transaction->value = $customerHistory->amount;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_DEBIT;
            $transaction->author = $customerHistory->author;
            $transaction->customer_account_history_id = $customerHistory->id;
            $transaction->order_id = $customerHistory->order_id;
            $transaction->name = sprintf( __( 'Customer Account Purchase : %s' ), $customerHistory->customer->name );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $customerHistory->created_at;
            $transaction->updated_at = $customerHistory->updated_at;

            $this->recordTransactionHistory( $transaction );
        } elseif ( in_array(
            $customerHistory->operation, [
                CustomerAccountHistory::OPERATION_DEDUCT,
            ]
        ) ) {
            $transactionAccount = $this->getTransactionAccountByCode( TransactionHistory::ACCOUNT_CUSTOMER_DEBIT );

            $transaction = new Transaction;
            $transaction->value = $customerHistory->amount;
            $transaction->active = true;
            $transaction->operation = TransactionHistory::OPERATION_DEBIT;
            $transaction->author = $customerHistory->author;
            $transaction->customer_account_history_id = $customerHistory->id;
            $transaction->name = sprintf( __( 'Customer Account Deducting : %s' ), $customerHistory->customer->name );
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $customerHistory->created_at;
            $transaction->updated_at = $customerHistory->updated_at;

            $this->recordTransactionHistory( $transaction );
        }
    }

    public function handleCashOperation( RegisterHistory $registerHistory )
    {
        if ( in_array( $registerHistory->action, [ 
                RegisterHistory::ACTION_CASHING, 
                RegisterHistory::ACTION_CASHOUT,
                RegisterHistory::ACTION_OPENING,
                RegisterHistory::ACTION_CLOSING,
            ] ) ) {
            
            $registerHistory->load( 'register' );

            $code   =   match( $registerHistory->action ) {
                RegisterHistory::ACTION_CASHING =>  TransactionHistory::ACCOUNT_REGISTER_CASHIN,
                RegisterHistory::ACTION_OPENING =>  TransactionHistory::ACCOUNT_REGISTER_CASHIN,
                RegisterHistory::ACTION_CASHOUT =>  TransactionHistory::ACCOUNT_REGISTER_CASHOUT,
                RegisterHistory::ACTION_CLOSING =>  TransactionHistory::ACCOUNT_REGISTER_CASHOUT,
            };

            $transactionAccount = $this->getTransactionAccountByCode( $code );

            $transaction = new Transaction;
            $transaction->value = $registerHistory->value;
            $transaction->active = true;
            $transaction->operation = match( $registerHistory->action ) {
                RegisterHistory::ACTION_CASHING =>  TransactionHistory::OPERATION_CREDIT,
                RegisterHistory::ACTION_OPENING =>  TransactionHistory::OPERATION_CREDIT,
                RegisterHistory::ACTION_CASHOUT =>  TransactionHistory::OPERATION_DEBIT,
                RegisterHistory::ACTION_CLOSING =>  TransactionHistory::OPERATION_DEBIT,
            };
            $transaction->author = $registerHistory->author;
            $transaction->register_history_id = $registerHistory->id;
            $transaction->name = match( $registerHistory->action ) {
                RegisterHistory::ACTION_CASHING =>  sprintf( __( 'Cash In : %s'), $registerHistory->register->name ),
                RegisterHistory::ACTION_OPENING =>  sprintf( __( 'Cash In : %s'), $registerHistory->register->name ),
                RegisterHistory::ACTION_CASHOUT =>  sprintf( __( 'Cash Out : %s'), $registerHistory->register->name ),
                RegisterHistory::ACTION_CLOSING =>  sprintf( __( 'Cash Out : %s'), $registerHistory->register->name ),
            };
            $transaction->id = 0; // this is not assigned to an existing transaction
            $transaction->account = $transactionAccount;
            $transaction->created_at = $registerHistory->created_at;
            $transaction->updated_at = $registerHistory->updated_at;

            $this->recordTransactionHistory( $transaction );  
        }
    }

    public function getConfigurations( Transaction $transaction )
    {
        $recurringFields = new ReccurringTransactionFields( $transaction );
        $directFields = new DirectTransactionFields( $transaction );
        $entityFields = new EntityTransactionFields( $transaction );
        $scheduledFields = new ScheduledTransactionFields( $transaction );

        $asyncTransactions = [];
        $warningMessage = false;

        /**
         * Those features can only be enabled
         * if the jobs are configured correctly.
         */
        if ( ns()->canPerformAsynchronousOperations() ) {
            $asyncTransactions = [
                [
                    'identifier' => ReccurringTransactionFields::getIdentifier(),
                    'label' => __( 'Recurring Transaction' ),
                    'icon' => asset( 'images/recurring.png' ),
                    'fields' => $recurringFields->get(),
                ], [
                    'identifier' => EntityTransactionFields::getIdentifier(),
                    'label' => __( 'Entity Transaction' ),
                    'icon' => asset( 'images/salary.png' ),
                    'fields' => $entityFields->get(),
                ], [
                    'identifier' => ScheduledTransactionFields::getIdentifier(),
                    'label' => __( 'Scheduled Transaction' ),
                    'icon' => asset( 'images/schedule.png' ),
                    'fields' => $scheduledFields->get(),
                ],
            ];
        } else {
            $warningMessage = sprintf(
                __( 'Some transactions are disabled as NexoPOS is not able to <a target="_blank" href="%s">perform asynchronous requests</a>.' ),
                'https://my.nexopos.com/en/documentation/troubleshooting/workers-or-async-requests-disabled'
            );
        }

        $configurations = Hook::filter( 'ns-transactions-configurations', [
            [
                'identifier' => DirectTransactionFields::getIdentifier(),
                'label' => __( 'Direct Transaction' ),
                'icon' => asset( 'images/budget.png' ),
                'fields' => $directFields->get(),
            ], ...$asyncTransactions,
        ]);

        $recurrence = Hook::filter( 'ns-transactions-recurrence', [
            [
                'type' => 'select',
                'label' => __( 'Condition' ),
                'name' => 'occurrence',
                'value' => $transaction->occurrence ?? '',
                'options' => Helper::kvToJsOptions([
                    Transaction::OCCURRENCE_START_OF_MONTH => __( 'First Day Of Month' ),
                    Transaction::OCCURRENCE_END_OF_MONTH => __( 'Last Day Of Month' ),
                    Transaction::OCCURRENCE_MIDDLE_OF_MONTH => __( 'Month middle Of Month' ),
                    Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS => __( '{day} after month starts' ),
                    Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS => __( '{day} before month ends' ),
                    Transaction::OCCURRENCE_SPECIFIC_DAY => __( 'Every {day} of the month' ),
                ]),
            ], [
                'type' => 'number',
                'label' => __( 'Days' ),
                'name' => 'occurrence_value',
                'value' => $transaction->occurrence_value ?? 0,
                'shows' => [
                    'occurrence' => [
                        Transaction::OCCURRENCE_X_AFTER_MONTH_STARTS,
                        Transaction::OCCURRENCE_X_BEFORE_MONTH_ENDS,
                        Transaction::OCCURRENCE_SPECIFIC_DAY,
                    ],
                ],
                'description' => __( 'Make sure set a day that is likely to be executed' ),
            ],
        ]);

        return compact( 'recurrence', 'configurations', 'warningMessage' );
    }
}
