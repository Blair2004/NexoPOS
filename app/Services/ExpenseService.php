<?php
namespace App\Services;

use App\Events\ExpenseAfterCreateEvent;
use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use App\Models\ExpenseCategory;
use App\Exceptions\NotFoundException;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
use App\Models\Procurement;
use App\Models\RegisterHistory;
use App\Models\Role;
use Carbon\Carbon;

class ExpenseService 
{
    /**
     * @var DateService
     */
    protected $dateService;

    protected $accountTypes  =   [
        CashFlow::ACCOUNT_SALES             =>  [ 'operation' => CashFlow::OPERATION_CREDIT, 'option' => 'ns_sales_cashflow_account' ],
        CashFlow::ACCOUNT_REFUNDS           =>  [ 'operation' => CashFlow::OPERATION_DEBIT, 'option' => 'ns_sales_refunds_account' ],
        CashFlow::ACCOUNT_SPOILED           =>  [ 'operation' => CashFlow::OPERATION_DEBIT, 'option' => 'ns_stock_return_spoiled_account' ],
        CashFlow::ACCOUNT_PROCUREMENTS      =>  [ 'operation' => CashFlow::OPERATION_DEBIT, 'option' => 'ns_procurement_cashflow_account' ],
        CashFlow::ACCOUNT_REGISTER_CASHIN   =>  [ 'operation' => CashFlow::OPERATION_CREDIT, 'option' => 'ns_cashregister_cashin_cashflow_account' ],
        CashFlow::ACCOUNT_REGISTER_CASHOUT  =>  [ 'operation' => CashFlow::OPERATION_DEBIT, 'option' => 'ns_cashregister_cashout_cashflow_account' ],
        CashFlow::ACCOUNT_CUSTOMER_CREDIT   =>  [ 'operation' => CashFlow::OPERATION_CREDIT, 'option' => 'ns_customer_crediting_cashflow_account' ],
        CashFlow::ACCOUNT_CUSTOMER_DEBIT    =>  [ 'operation' => CashFlow::OPERATION_DEBIT, 'option' => 'ns_customer_debitting_cashflow_account' ],
    ];

    public function __construct( DateService $dateService )
    {   
        $this->dateService      =   $dateService;
    }
    
    public function create( $fields )
    {
        $expense    =   new Expense;

        foreach( $fields as $field => $value ) {
            $expense->$field    =   $value;
        }

        $expense->author        =   Auth::id();
        $expense->save();

        event( new ExpenseAfterCreateEvent( $expense, request() ) );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense has been successfully saved.' ),
            'data'      =>  compact( 'expense' )
        ];
    }

    public function edit( $id, $fields )
    {
        $expense    =   $this->get( $id );

        if ( $expense instanceof Expense ) {
            
            foreach( $fields as $field => $value ) {
                $expense->$field    =   $value;
            }

            $expense->author        =   Auth::id();
            $expense->save();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The expense has been successfully updated.' ),
                'data'      =>  compact( 'expense' )
            ];
        }

        throw new NotFoundException( __( 'Unable to find the expense using the provided identifier.' ) );
    }

    /**
     * get a specific expense using
     * the provided id
     * @param int expense id
     * @return Collection|Expense|NotFoundException
     */
    public function get( $id = null ) 
    {
        if ( $id === null ) {
            return Expense::get();
        }

        $expense    =   Expense::find( $id );
        
        if ( ! $expense instanceof Expense ) {
            throw new NotFoundException( __( 'Unable to find the requested expense using the provided id.' ) );
        }

        return $expense;
    }

    /**
     * Delete an expense using the 
     * provided id
     * @param int expense id
     * @return array
     */
    public function delete( $id )
    {
        $expense        =   $this->get( $id );              
        $expense->delete();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense has been correctly deleted.' )
        ];
    }

    /**
     * @deprecated
     */
    public function getCategories( $id = null )
    {
        if ( $id !== null ) {
            $category   =   ExpenseCategory::find( $id );
            if ( ! $category instanceof ExpenseCategory ) {
                throw new NotFoundException( __( 'Unable to find the requested expense category using the provided id.' ) );
            }

            return $category;
        }

        return ExpenseCategory::get();
    }

    /**
     * Retreive a specific account type 
     * or all accuont type
     */
    public function getAccountTypeByID( $id )
    {
        if ( $id !== null ) {
            $category   =   AccountType::find( $id );
            if ( ! $category instanceof AccountType ) {
                throw new NotFoundException( __( 'Unable to find the requested account type using the provided id.' ) );
            }

            return $category;
        }

        return AccountType::get();
    }

    /**
     * Delete specific account type
     * @todo must be implemented
     */
    public function deleteAccountType( $id, $force = true )
    {
        $accountType    =   $this->getAccountTypeByID( $id );

        if ( $accountType->expenses->count() > 0 && $force === false ) {
            throw new NotAllowedException( __( 'You cannot delete an account type that has transaction bound.' ) );
        }

        /**
         * if there is not expense, it 
         * won't be looped
         */
        $accountType->expenses->map( function( $expense ) {
            $expense->delete();
        });

        $accountType->delete();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The account type has been deleted.' )
        ];
    }

    /**
     * Delete a specific category
     * using the provided id, along with the expenses
     * @param int id
     * @param boolean force deleting
     * @return array|NotAllowedException
     */
    public function deleteCategory( $id, $force = false )
    {
        $expenseCategory    =   $this->getCategories( $id );

        if ( $expenseCategory->expenses->count() > 0 && $force === false ) {
            throw new NotAllowedException( __( 'You cannot delete a category which has expenses bound.' ) );
        }

        /**
         * if there is not expense, it 
         * won't be looped
         */
        $expenseCategory->expenses->map( function( $expense ) {
            $expense->delete();
        });

        $expenseCategory->delete();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense category has been deleted.' )
        ];
    }

    /**
     * Get a specific expense
     * category using the provided ID
     * @param int expense category id
     * @return void|Collection
     */
    public function getCategory( $id )
    {
        $expenseCategory    =   ExpenseCategory::find( $id );
        
        if ( ! $expenseCategory instanceof ExpenseCategory ) {
            throw new NotFoundException( __( 'Unable to find the expense category using the provided ID.' ) );
        }

        return $expenseCategory;
    }

    /**
     * Create a category using 
     * the provided details
     * @param array category detail
     * @return array status of the operation
     * @deprecated
     */
    public function createCategory( $fields )
    {
        $category    =   new ExpenseCategory;

        foreach( $fields as $field => $value ) {
            $category->$field    =   $value;
        }

        $category->author    =   Auth::id();
        $category->save();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense category has been saved' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * Creates an accounting account
     * @param array $fields
     * @return array status
     */
    public function createAccount( $fields )
    {
        $category    =   new AccountType;

        foreach( $fields as $field => $value ) {
            $category->$field    =   $value;
        }

        $category->author    =   Auth::id();
        $category->save();
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The account has been created.' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * Update specified expense
     * category using a provided ID
     * @param int expense category ID
     * @param array of values to update
     * @return array operation status
     */
    public function editCategory( $id, $fields )
    {
        $category    =   $this->getCategory( $id );

        foreach( $fields as $field => $value ) {
            $category->$field    =   $value;
        }

        $category->author        =   Auth::id();
        $category->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The expense category has been updated.' ),
            'data'      =>  compact( 'category' )
        ];
    }

    /**
     * Will trigger for not recurring expense
     * @param Expense $expense
     * @return void
     */
    public function triggerExpense( $expense )
    {
        $this->recordCashFlowHistory( $expense );

        /**
         * a non recurring expenses
         * once triggered should be disabled to 
         * prevent futher execution on modification.
         */
        $expense->active    =   false;
        $expense->save();
    }

    public function getCategoryExpense( $id )
    {
        $expenseCategory    =   $this->getCategory( $id );
        return $expenseCategory->expenses;
    }

    public function recordCashFlowHistory( Expense $expense )
    {
        if ( ! empty( $expense->group_id  ) ) {
            Role::find( $expense->group_id )->users->each( function( $user ) use ( $expense ) {
                $history                            =   new CashFlow;
                $history->value                     =   $expense->value;
                $history->expense_id                =   $expense->id;
                $history->operation                 =   'debit';
                $history->author                    =   $expense->author;
                $history->name                      =   str_replace( '{user}', ucwords( $user->username ), $expense->name );
                $history->expense_category_id       =   $expense->category->id;

                /**
                 * Just in case we want to set the CashFlow as having been
                 * created at a specific moment
                 */
                if ( isset( $expense->created_at ) ) {
                    $history->timestamps    =   false;
                    $history->created_at    =   $expense->created_at;
                }

                if ( isset( $expense->updated_at ) ) {
                    $history->timestamps    =   false;
                    $history->updated_at    =   $expense->updated_at;
                }

                $history->save();

                event( new CashFlowHistoryAfterCreatedEvent( $history ) );
            });
        } else {
            $history                            =   new CashFlow;
            $history->value                     =   $expense->value;
            $history->expense_id                =   $expense->id;
            $history->operation                 =   $expense->operation ?? 'debit'; // if the operation is not defined, by default is a "debit"
            $history->author                    =   $expense->author;
            $history->name                      =   $expense->name;
            $history->procurement_id            =   $expense->procurement_id ?? 0; // if the cash flow is created from a procurement
            $history->order_id                  =   $expense->order_id ?? 0; // if the cash flow is created from a refund
            $history->register_history_id       =   $expense->register_history_id ?? 0; // if the cash flow is created from a register transaction
            $history->expense_category_id       =   $expense->category->id;

            /**
             * Just in case we want to set the CashFlow as having been
             * created at a specific moment
             */
            if ( isset( $expense->created_at ) ) {
                $history->timestamps    =   false;
                $history->created_at    =   $expense->created_at;
            }

            if ( isset( $expense->updated_at ) ) {
                $history->timestamps    =   false;
                $history->updated_at    =   $expense->updated_at;
            }

            $history->save();

            event( new CashFlowHistoryAfterCreatedEvent( $history ) );
        }
    }

    /**
     * Process recorded expenses
     * and check wether they are supposed to be processed 
     * on the current day. 
     * 
     * @return array of process results.
     */
    public function handleRecurringExpenses()
    {
        $processStatus      =   Expense::recurring()
            ->active()
            ->get()
            ->map( function( $expense ) {
                switch( $expense->occurence ) {
                    case 'month_starts':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->startOfMonth() );   
                    break;
                    case 'month_mid':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->startOfMonth()->addDays(14) );
                    break;
                    case 'month_ends':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->endOfMonth() );
                    break;
                    case 'x_before_month_ends':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->endOfMonth()->subDays( $expense->occurence_value ) );
                    break;
                    case 'x_after_month_starts':
                        $expenseScheduledDate   =   Carbon::parse( $this->dateService->copy()->startOfMonth()->addDays( $expense->occurence_value ) );
                    break;
                }

                /**
                 * Checks if the recurring expenses about to be saved has been
                 * already issued on the occuring day.
                 */
                if ( $this->dateService->isSameDay( $expenseScheduledDate ) ) {
                    
                    if ( ! $this->hadCashFlowRecordedAlready( $expenseScheduledDate, $expense ) ) {
                        
                        $this->recordCashFlowHistory( $expense );
                        
                        return [
                            'status'    =>  'success',
                            'message'   =>  sprintf( __( 'The expense "%s" has been processed.' ), $expense->name ),
                        ];
                    } 

                    return [
                        'status'    =>  'failed',
                        'message'   =>  sprintf( __( 'The expense "%s" has already been processed.' ), $expense->name ),
                    ];
                } 

                return [
                    'status'    =>  'failed',
                    'message'   =>  sprintf( __( 'The expenses "%s" hasn\'t been proceesed it\'s out of date.' ), $expense->name )
                ];

            });

        $successFulProcesses    =   collect( $processStatus )->filter( fn( $process ) => $process[ 'status' ] === 'success' );

        return [
            'status'    =>  'success',
            'data'      =>  $processStatus->toArray(),
            'message'   =>  $successFulProcesses->count() === $processStatus->count() ?
                __( 'The process has been correctly executed and all expenses has been processed.' ) :
                    sprintf( __( 'The process has been executed with some failures. %s/%s process(es) has successed.' ), $successFulProcesses->count(), $processStatus->count() )
        ];
    }

    /**
     * Check if an expense has been executed during a day.
     * To prevent many recurring expenses to trigger multiple times
     * during a day.
     */
    public function hadCashFlowRecordedAlready( $date, Expense $expense )
    {
        $history    =   CashFlow::where( 'expense_id', $expense->id )
            ->where( 'created_at', '>=', $date->startOfDay()->toDateTimeString() )
            ->where( 'created_at', '<=', $date->endOfDay()->toDateTimeString() )
            ->get();

        return $history instanceof CashFlow;
    }

    /**
     * Will record an expense resulting from a paid procurement
     * @param Procurement $procurement
     * @return void
     */
    public function handleProcurementExpense( Procurement $procurement )
    {
        if ( 
            $procurement->payment_status === Procurement::PAYMENT_PAID &&
            $procurement->delivery_status === Procurement::STOCKED
        ) {
            $expenseCategory    =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_PROCUREMENTS );
                                    
            /**
             * this behave as a flash expense
             * made only for recording an history.
             */
            $expense                    =   new Expense;
            $expense->value             =   $procurement->cost;
            $expense->active            =   true;
            $expense->author            =   Auth::id();
            $expense->procurement_id    =   $procurement->id;
            $expense->name              =   sprintf( __( 'Procurement : %s' ), $procurement->name );
            $expense->id                =   0; // this is not assigned to an existing expense
            $expense->category          =   $expenseCategory;
            $expense->created_at        =   $procurement->created_at;
            $expense->updated_at        =   $procurement->updated_at;

            $this->recordCashFlowHistory( $expense );
        }
    }

    /**
     * Will record an expense for every refund performed
     * @param OrderProduct $orderProduct
     * @return void
     */
    public function createExpenseFromRefund( Order $order, OrderProductRefund $orderProductRefund, OrderProduct $orderProduct )
    {
        $expenseCategory    =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_REFUNDS );
                                
        /**
         * Every product refund produce a debit
         * operation on the system.
         */
        $expense                    =   new Expense;
        $expense->value             =   $orderProductRefund->total_price;
        $expense->active            =   true;
        $expense->operation         =   CashFlow::OPERATION_DEBIT;
        $expense->author            =   Auth::id();
        $expense->order_id          =   $order->id;
        $expense->order_refund_id   =   $orderProductRefund->order_refund_id;
        $expense->name              =   sprintf( __( 'Refunding : %s' ), $orderProduct->name );
        $expense->id                =   0; // this is not assigned to an existing expense
        $expense->category          =   $expenseCategory;

        $this->recordCashFlowHistory( $expense );

        if ( OrderProductRefund::CONDITION_DAMAGED ) {
            /**
             * Only if the product is damaged we should
             * consider saving that as a waste.
             */
            $expenseCategory    =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_SPOILED );

            $expense                    =   new Expense;
            $expense->value             =   $orderProductRefund->total_price;
            $expense->active            =   true;
            $expense->operation         =   CashFlow::OPERATION_DEBIT;
            $expense->author            =   Auth::id();
            $expense->order_id          =   $order->id;
            $expense->order_refund_id   =   $orderProductRefund->order_refund_id;
            $expense->name              =   sprintf( __( 'Spoiled Good : %s' ), $orderProduct->name );
            $expense->id                =   0; // this is not assigned to an existing expense
            $expense->category          =   $expenseCategory;
    
            $this->recordCashFlowHistory( $expense );
        }
    }

    /**
     * If the order has just been
     * created and the payment status is PAID
     * we'll store the total as a cash flow transaction.
     * @param Order $order
     * @return void
     */
    public function handleCreatedOrder( Order $order )
    {
        $expenseCategory    =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_SALES );

        $expense                        =   new Expense;
        $expense->value                 =   $order->total;
        $expense->active                =   true;
        $expense->operation             =   CashFlow::OPERATION_CREDIT;
        $expense->author                =   Auth::id();
        $expense->order_id              =   $order->id;
        $expense->name                  =   sprintf( __( 'Sale : %s' ), $order->code );
        $expense->id                    =   0; // this is not assigned to an existing expense
        $expense->category              =   $expenseCategory;
        $expense->created_at            =   $order->created_at;
        $expense->updated_at            =   $order->updated_at;

        $this->recordCashFlowHistory( $expense );
    }

    /**
     * Will pul the defined account
     * or will create a new one according to the settings
     * @param string $accountSettingsName
     * @param array $defaults
     * @return ExpenseCategory $expenseCategory
     */
    public function getDefinedAccountType( $accountSettingsName, $defaults )
    {
        $accountType    =   AccountType::find( ns()->option->get( $accountSettingsName ) );

        if ( ! $accountType instanceof AccountType ) {
            $result     =   $this->createAccount( $defaults );

            $accountType    =   ( object ) $result[ 'data' ][ 'category' ];
            
            /**
             * Will set the expense as the default category expense
             * category for subsequent expenses.
             */
            ns()->option->set( $accountSettingsName, $accountType->id );

            $accountType    =   AccountType::find( ns()->option->get( $accountSettingsName ) );
        } 

        return $accountType;
    }

    /**
     * Will compare order payment status
     * and if the previous and the new payment status are supported
     * the transaction will be record to the cash flow
     * @param OrderAfterPaymentStatusChangedEvent $event
     * @return void
     */
    public function handlePaymentStatus( OrderAfterPaymentStatusChangedEvent $event )
    {
        if ( in_array( $event->previous, [
            Order::PAYMENT_HOLD,
            Order::PAYMENT_DUE,
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_PARTIALLY_DUE,
            Order::PAYMENT_UNPAID
        ]) && in_array(
            $event->new, [
                Order::PAYMENT_PAID
            ]
        )) {
            $expenseCategory                =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_SALES );   

            $expense                        =   new Expense;
            $expense->value                 =   $event->order->total;
            $expense->active                =   true;
            $expense->operation             =   CashFlow::OPERATION_CREDIT;
            $expense->author                =   Auth::id();
            $expense->order_id              =   $event->order->id;
            $expense->name                  =   sprintf( __( 'Sale : %s' ), $event->order->code );
            $expense->id                    =   0; // this is not assigned to an existing expense
            $expense->category              =   $expenseCategory;

            $this->recordCashFlowHistory( $expense );
        }
    }

    public function recomputeCashFlow( $rangeStarts = null, $rangeEnds = null )
    {
        /**
         * We'll register cash flow for complete orders
         */
        $this->processPaidOrders( $rangeStarts, $rangeEnds );
        $this->processCustomerAccountHistories( $rangeStarts, $rangeEnds );
        $this->processExpenses( $rangeStarts, $rangeEnds );
        $this->processProcurements( $rangeStarts, $rangeEnds );
        $this->processRecurringExpenses( $rangeStarts, $rangeEnds );
        $this->processRefundedOrders( $rangeStarts, $rangeEnds );
    }

    /**
     * Retreive the account configuration 
     * using the account type
     * @param string $type
     * @return ExpenseCategory|AccountType
     */
    public function getAccountTypeByCode( $type )
    {
        $account    =   $this->accountTypes[ $type ] ?? false;

        if ( ! empty( $account ) ) {

            /**
             * This will define the label
             */
            switch( $type ) {
                case CashFlow::ACCOUNT_CUSTOMER_CREDIT : $label = __( 'Customer Credit Account' ); break;
                case CashFlow::ACCOUNT_CUSTOMER_DEBIT : $label = __( 'Customer Debit Account' ); break;
                case CashFlow::ACCOUNT_PROCUREMENTS : $label = __( 'Procurements Account' ); break;
                case CashFlow::ACCOUNT_REFUNDS : $label = __( 'Sales Refunds Account' ); break;
                case CashFlow::ACCOUNT_REGISTER_CASHIN : $label = __( 'Register Cash-In Account' ); break;
                case CashFlow::ACCOUNT_REGISTER_CASHOUT : $label = __( 'Register Cash-Out Account' ); break;
                case CashFlow::ACCOUNT_SALES : $label = __( 'Sales Account' ); break;
                case CashFlow::ACCOUNT_SPOILED : $label = __( 'Spoiled Goods Account' ); break;
            }

            return $this->getDefinedAccountType( $account[ 'option' ], [
                'name'      =>  $label,
                'operation' =>  $account[ 'operation' ],
                'account'   =>  $type
            ]);
        }

        throw new NotFoundException( sprintf(
            __( 'Not found account type: %s' ),
            $type
        ) );
    }

    /**
     * Will process refunded orders
     * @param string $rangeStart
     * @param string $rangeEnds
     * @return void
     */
    public function processRefundedOrders( $rangeStarts, $rangeEnds )
    {
        $orders     =   Order::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->paymentStatus( Order::PAYMENT_REFUNDED )
            ->get();

        $expenseCategory                =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_REFUNDS );   

        $orders->each( function( $order ) use ( $expenseCategory ) {
            $expense                                =   new Expense;
            $expense->value                         =   $order->total;
            $expense->active                        =   true;
            $expense->operation                     =   CashFlow::OPERATION_DEBIT;
            $expense->author                        =   Auth::id();
            $expense->customer_account_history_id   =   $order->id;
            $expense->name                          =   sprintf( __( 'Refund : %s' ), $order->code );
            $expense->id                            =   0; // this is not assigned to an existing expense
            $expense->category                      =   $expenseCategory;
            $expense->created_at                    =   $order->created_at;
            $expense->updated_at                    =   $order->updated_at;

            $this->recordCashFlowHistory( $expense );
        });
    }

    /**
     * Will process paid orders
     * @param string $rangeStart
     * @param string $rangeEnds
     * @return void
     */
    public function processPaidOrders( $rangeStart, $rangeEnds )
    {
        $orders     =   Order::where( 'created_at', '>=', $rangeStart )
            ->with( 'customer' )
            ->where( 'created_at', '<=', $rangeEnds )
            ->paymentStatus( Order::PAYMENT_PAID )
            ->get();

        $expenseCategory                =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_SALES );   

        Customer::where( 'id', '>', 0 )->update([ 'purchases_amount' => 0 ]);

        $orders->each( function( $order ) use ( $expenseCategory ) {
            $expense                                =   new Expense;
            $expense->value                         =   $order->total;
            $expense->active                        =   true;
            $expense->operation                     =   CashFlow::OPERATION_CREDIT;
            $expense->author                        =   Auth::id();
            $expense->customer_account_history_id   =   $order->id;
            $expense->name                          =   sprintf( __( 'Sale : %s' ), $order->code );
            $expense->id                            =   0; // this is not assigned to an existing expense
            $expense->category                      =   $expenseCategory;
            $expense->created_at                    =   $order->created_at;
            $expense->updated_at                    =   $order->updated_at;

            $customer   =   Customer::find( $order->customer_id );

            if ( $customer instanceof Customer ) {
                $customer->purchases_amount     +=  $order->total;
                $customer->save();
            }

            $this->recordCashFlowHistory( $expense );
        });
    }

    /**
     * Will process the customer histories
     * @return void
     */
    public function processCustomerAccountHistories( $rangeStarts, $rangeEnds )
    {
        $histories  =   CustomerAccountHistory::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->get();
        $histories->each( function( $history ) {
            $this->handleCustomerCredit( $history );
        });
    }

    /**
     * Will create an expense for each created procurement
     * @return void
     */
    public function processProcurements( $rangeStarts, $rangeEnds )
    {
        Procurement::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->get()->each( function( $procurement ) {
                $this->handleProcurementExpense( $procurement );
        });
    }

    /**
     * Will trigger not recurring expense
     * @return void
     */
    public function processExpenses( $rangeStarts, $rangeEnds )
    {
        Expense::where( 'created_at', '>=', $rangeStarts )
            ->where( 'created_at', '<=', $rangeEnds )
            ->notRecurring()
            ->get()
            ->each( function( $expense ) {
                $this->triggerExpense( $expense );
        });
    }

    public function processRecurringExpenses( $rangeStart, $rangeEnds )
    {
        $startDate  =   Carbon::parse( $rangeStart );
        $endDate    =   Carbon::parse( $rangeEnds );
        $current    =   ns()->date->copy();

        if ( $startDate->lessThan( $endDate ) && $startDate->diffInDays( $endDate ) >= 1 ) {
            while( $startDate->isSameDay() ) {
                ns()->date      =   $startDate;

                $this->handleRecurringExpenses();

                $startDate->addDay();
            }
        }
    }

    /**
     * Will add customer credit operation 
     * to the cash flow history
     * @param CustomerAccountHistory $customerHistory
     * @return void
     */
    public function handleCustomerCredit( CustomerAccountHistory $customerHistory )
    {
        if ( in_array( $customerHistory->operation, [
            CustomerAccountHistory::OPERATION_ADD,
            CustomerAccountHistory::OPERATION_REFUND,
            CustomerAccountHistory::OPERATION_PAYMENT,
        ]) ) {
            $expenseCategory                        =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_CUSTOMER_CREDIT );   

            $expense                                =   new Expense;
            $expense->value                         =   $customerHistory->amount;
            $expense->active                        =   true;
            $expense->operation                     =   CashFlow::OPERATION_CREDIT;
            $expense->author                        =   Auth::id();
            $expense->customer_account_history_id   =   $customerHistory->id;
            $expense->name                          =   sprintf( __( 'Customer Account Crediting : %s' ), $customerHistory->customer->name );
            $expense->id                            =   0; // this is not assigned to an existing expense
            $expense->category                      =   $expenseCategory;
            $expense->created_at                    =   $customerHistory->created_at;
            $expense->updated_at                    =   $customerHistory->updated_at;

            $this->recordCashFlowHistory( $expense );
        } else if ( in_array(
            $customerHistory->operation, [
                CustomerAccountHistory::OPERATION_DEDUCT,
            ]
        ) ) {
            $expenseCategory                        =   $this->getAccountTypeByCode( CashFlow::ACCOUNT_CUSTOMER_DEBIT );   
    
            $expense                                =   new Expense;
            $expense->value                         =   $customerHistory->amount;
            $expense->active                        =   true;
            $expense->operation                     =   CashFlow::OPERATION_DEBIT;
            $expense->author                        =   Auth::id();
            $expense->customer_account_history_id   =   $customerHistory->id;
            $expense->name                          =   sprintf( __( 'Customer Account Deducting : %s' ), $customerHistory->customer->name );
            $expense->id                            =   0; // this is not assigned to an existing expense
            $expense->category                      =   $expenseCategory;
            $expense->created_at                    =   $customerHistory->created_at;
            $expense->updated_at                    =   $customerHistory->updated_at;

            $this->recordCashFlowHistory( $expense );
        }        
    }
}