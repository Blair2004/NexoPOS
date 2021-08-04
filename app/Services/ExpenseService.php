<?php
namespace App\Services;

use App\Events\ExpenseAfterCreateEvent;
use App\Events\CashFlowHistoryAfterCreatedEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use App\Models\ExpenseCategory;
use App\Exceptions\NotFoundException;
use App\Models\CashFlow;
use App\Models\CustomerAccountHistory;
use App\Models\Order;
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
                $history->name              =   str_replace( '{user}', ucwords( $user->username ), $expense->name );
                $history->expense_category_id       =   $expense->category->id;
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
            $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_procurement_cashflow_account' ) );

            if ( ! $expenseCategory instanceof ExpenseCategory ) {
                $result     =   $this->createCategory([
                    'name'  =>  __( 'Procurement Expenses' ),
                ]);

                $expenseCategory    =   ( object ) $result[ 'data' ][ 'category' ];
                
                /**
                 * Will set the expense as the default category expense
                 * category for subsequent expenses.
                 */
                ns()->option->set( 'ns_procurement_cashflow_account', $expenseCategory->id );
            }
                                    
            /**
             * this behave as a flash expense
             * made only for recording an history.
             */
            $expense                    =   new Expense;
            $expense->value             =   $procurement->value;
            $expense->active            =   true;
            $expense->author            =   Auth::id();
            $expense->procurement_id    =   $procurement->id;
            $expense->name              =   sprintf( __( 'Procurement : %s' ), $procurement->name );
            $expense->id                =   0; // this is not assigned to an existing expense
            $expense->category          =   $expenseCategory;

            $this->recordCashFlowHistory( $expense );
        }
    }

    /**
     * Will record an expense for every refund performed
     * @param OrderProduct $orderProduct
     * @return void
     */
    public function createExpenseFromRefund( OrderProductRefund $orderProductRefund, OrderProduct $orderProduct )
    {
        $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_sales_refunds_account' ) );

        if ( ! $expenseCategory instanceof ExpenseCategory ) {
            $result     =   $this->createCategory([
                'name'  =>  __( 'Sales Refunds' ),
            ]);

            $expenseCategory    =   ( object ) $result[ 'data' ][ 'category' ];
            
            /**
             * Will set the expense as the default category expense
             * category for subsequent expenses.
             */
            ns()->option->set( 'ns_sales_refunds_account', $expenseCategory->id );
        }
                                
        /**
         * Every product refund produce a debit
         * operation on the system.
         */
        $expense                    =   new Expense;
        $expense->value             =   $orderProductRefund->total_price;
        $expense->active            =   true;
        $expense->operation         =   CashFlow::OPERATION_DEBIT;
        $expense->author            =   Auth::id();
        $expense->order_refund_id   =   $orderProductRefund->order_refund_id;
        $expense->name              =   sprintf( __( 'Refunding : %s' ), $orderProduct->name );
        $expense->id                =   0; // this is not assigned to an existing expense
        $expense->category          =   $expenseCategory;

        $this->recordCashFlowHistory( $expense );

        /**
         * According to wether the product was returned in good condition
         * we'll add a stock return or a waste.
         */
        $conditionLabel             =   $orderProduct->condition === OrderProductRefund::CONDITION_DAMAGED ? __( 'Soiled' ) : __( 'Unspoiled' );
        $optionName                 =   $orderProduct->condition === OrderProductRefund::CONDITION_DAMAGED ? 'ns_stock_return_spoiled_account' : 'ns_stock_return_unspoiled_account';
        $expenseCategory            =   ExpenseCategory::find( ns()->option->get( $optionName ) );

        if ( ! $expenseCategory instanceof ExpenseCategory ) {
            $result     =   $this->createCategory([
                'name'  =>  $orderProduct->condition === OrderProductRefund::CONDITION_DAMAGED ? __( 'Stock Return (Spoiled Products)' ) : __( 'Stock Return (Unspoiled Products)' ),
            ]);

            $expenseCategory    =   ( object ) $result[ 'data' ][ 'category' ];
            
            /**
             * Will set the expense as the default category expense
             * category for subsequent expenses.
             */
            ns()->option->set( $optionName, $expenseCategory->id );
        }

        $expense                    =   new Expense;
        $expense->value             =   $orderProductRefund->total_price;
        $expense->active            =   true;
        $expense->operation         =   $orderProduct->condition === OrderProductRefund::CONDITION_DAMAGED ? CashFlow::OPERATION_DEBIT : CashFlow::OPERATION_CREDIT;
        $expense->author            =   Auth::id();
        $expense->order_refund_id   =   $orderProductRefund->order_refund_id;
        $expense->name              =   sprintf( __( 'Stock Return (%s) : %s' ), $conditionLabel, $orderProduct->name );
        $expense->id                =   0; // this is not assigned to an existing expense
        $expense->category          =   $expenseCategory;

        $this->recordCashFlowHistory( $expense );
    }

    public function handleCashRegisterHistory( RegisterHistory $history )
    {                               
        /**
         * this behave as a flash expense
         * made only for recording an history.
         */
        if ( in_array( $history->action, [
            RegisterHistory::ACTION_CASHING,
            // RegisterHistory::ACTION_SALE, // we want to consider sales separately
            RegisterHistory::ACTION_OPENING
        ])) {
            $operation      =   'credit';
            $expenseCategory        =   $this->__getCashFlowCategory( $operation );
        } else {
            $operation      =   'debit';
            $expenseCategory        =   $this->__getCashFlowCategory( $operation );
        }

        /**
         * @var CashRegistersService
         */
        $registerService                =   app()->make( CashRegistersService::class );

        $expense                        =   new Expense;
        $expense->value                 =   $history->value;
        $expense->active                =   true;
        $expense->operation             =   $operation;
        $expense->author                =   Auth::id();
        $expense->register_history_id   =   $history->id;
        $expense->name                  =   sprintf( __( 'Cash Register : %s' ), $registerService->getActionLabel( $history->action ) );
        $expense->id                    =   0; // this is not assigned to an existing expense
        $expense->category              =   $expenseCategory;

        $this->recordCashFlowHistory( $expense );
    }

    /**
     * @param string $operation
     * @return ExpenseCategory
     */
    private function __getCashFlowCategory( $operation )
    {
        $optionName         =   $operation === 'debit' ? 'ns_cashregister_cashin_cashflow_account' : 'ns_cashregister_cashout_cashflow_account';
        $expenseCategory    =   ExpenseCategory::find( 
            ns()->option->get( $optionName )
        );

        if ( ! $expenseCategory instanceof ExpenseCategory ) {
            $result     =   $this->createCategory([
                'name'  =>  $operation === 'credit' ? __( 'Cash Register Cash In' ) : __( 'Cash Register Cash Out' ),
            ]);

            $expenseCategory    =   ( object ) $result[ 'data' ][ 'category' ];
            
            /**
             * Will set the expense as the default category expense
             * category for subsequent expenses.
             */
            ns()->option->set( $optionName, $expenseCategory->id );
        }

        return $expenseCategory;
    }

    /**
     * Will records salees on the cash flow history
     * @param Order $order
     * @return void
     */
    public function handleSales( Order $order )
    {
        if ( $order->payment_status === Order::PAYMENT_PAID ) {
            $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_sales_cashflow_account' ) );

            if ( ! $expenseCategory instanceof ExpenseCategory ) {
                $result     =   $this->createCategory([
                    'name'  =>  __( 'Sales' ),
                ]);

                $expenseCategory    =   ( object ) $result[ 'data' ][ 'category' ];
                
                /**
                 * Will set the expense as the default category expense
                 * category for subsequent expenses.
                 */
                ns()->option->set( 'ns_sales_cashflow_account', $expenseCategory->id );

                $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_sales_cashflow_account' ) );
            }

            if ( $expenseCategory instanceof ExpenseCategory ) {
                $expense                        =   new Expense;
                $expense->value                 =   $order->total;
                $expense->active                =   true;
                $expense->operation             =   CashFlow::OPERATION_CREDIT;
                $expense->author                =   Auth::id();
                $expense->order_id              =   $order->id;
                $expense->name                  =   sprintf( __( 'Sale : %s' ), $order->code );
                $expense->id                    =   0; // this is not assigned to an existing expense
                $expense->category              =   $expenseCategory;
    
                $this->recordCashFlowHistory( $expense );
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
            $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_customer_crediting_cashflow_account' ) );
    
            if ( $expenseCategory instanceof ExpenseCategory ) {
                $expense                                =   new Expense;
                $expense->value                         =   $customerHistory->amount;
                $expense->active                        =   true;
                $expense->operation                     =   CashFlow::OPERATION_CREDIT;
                $expense->author                        =   Auth::id();
                $expense->customer_account_history_id   =   $customerHistory->id;
                $expense->name                          =   sprintf( __( 'Customer Crediting : %s' ), $customerHistory->customer->name );
                $expense->id                            =   0; // this is not assigned to an existing expense
                $expense->category                      =   $expenseCategory;
    
                $this->recordCashFlowHistory( $expense );
            }
        } else if ( in_array(
            $customerHistory->operation, [
                CustomerAccountHistory::OPERATION_DEDUCT,
            ]
        ) ) {
            $expenseCategory    =   ExpenseCategory::find( ns()->option->get( 'ns_customer_debitting_cashflow_account' ) );
    
            if ( $expenseCategory instanceof ExpenseCategory ) {
                $expense                                =   new Expense;
                $expense->value                         =   $customerHistory->amount;
                $expense->active                        =   true;
                $expense->operation                     =   CashFlow::OPERATION_DEBIT;
                $expense->author                        =   Auth::id();
                $expense->customer_account_history_id   =   $customerHistory->id;
                $expense->name                          =   sprintf( __( 'Customer Crediting Deducting : %s' ), $customerHistory->customer->name );
                $expense->id                            =   0; // this is not assigned to an existing expense
                $expense->category                      =   $expenseCategory;
    
                $this->recordCashFlowHistory( $expense );
            }
        }        
    }
}