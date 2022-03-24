<?php
namespace App\Services;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderRefundPaymentAfterCreatedEvent;
use App\Exceptions\NotAllowedException;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Register;
use App\Models\RegisterHistory;
use Exception;
use Illuminate\Support\Facades\Auth;

class CashRegistersService
{
    public function openRegister( Register $register, $amount, $description )
    {
        if ( $register->status !== Register::STATUS_CLOSED ) {
            throw new NotAllowedException( 
                sprintf( 
                    __( 'Unable to open "%s" *, as it\'s not closed.' ),
                    $register->name
                )
            );
        }

        $registerHistory                =   new RegisterHistory;
        $registerHistory->register_id   =   $register->id;
        $registerHistory->action        =   RegisterHistory::ACTION_OPENING;
        $registerHistory->author        =   Auth::id();
        $registerHistory->description   =   $description;
        $registerHistory->value         =   $amount;
        $registerHistory->save();

        $register->status   =  Register::STATUS_OPENED;
        $register->used_by  =   Auth::id();
        $register->balance  =   $amount;
        $register->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The register has been successfully opened' ),
            'data'      =>  [
                'register'  =>  $register,
                'history'   =>  $registerHistory
            ]
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

        if ( ( float ) $register->balance !== ( float ) $amount ) {            
            throw new NotAllowedException( 
                sprintf( 
                    __( 'The specified amount %s doesn\'t match the cash register balance %s.' ),
                    ( string ) ns()->currency->fresh( $amount ),
                    ( string ) ns()->currency->fresh( $register->balance )
                )
            );
        }

        $registerHistory                =   new RegisterHistory;
        $registerHistory->register_id   =   $register->id;
        $registerHistory->action        =   RegisterHistory::ACTION_CLOSING;
        $registerHistory->author        =   Auth::id();
        $registerHistory->description   =   $description;
        $registerHistory->value         =   $amount;
        $registerHistory->save();

        $register->status   =  Register::STATUS_CLOSED;
        $register->used_by  =   null;
        $register->balance  =   0;
        $register->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The register has been successfully closed' ),
            'data'      =>  [
                'register'  =>  $register,
                'history'   =>  $registerHistory
            ]
        ];
    }

    public function cashIn( Register $register, $amount, $description )
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

        $registerHistory                =   new RegisterHistory;
        $registerHistory->register_id   =   $register->id;
        $registerHistory->action        =   RegisterHistory::ACTION_CASHING;
        $registerHistory->author        =   Auth::id();
        $registerHistory->description   =   $description;
        $registerHistory->value         =   $amount;
        $registerHistory->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The cash has successfully been stored' ),
            'data'      =>  [
                'register'  =>  $register,
                'history'   =>  $registerHistory
            ]
        ];
    }

    public function cashOut( Register $register, $amount, $description )
    {
        if ( $register->status !== Register::STATUS_OPENED ) {
            throw new NotAllowedException( 
                sprintf( 
                    __( 'Unable to cashout on "%s" *, as it\'s not opened.' ),
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

        $registerHistory                =   new RegisterHistory;
        $registerHistory->register_id   =   $register->id;
        $registerHistory->action        =   RegisterHistory::ACTION_CASHOUT;
        $registerHistory->author        =   Auth::id();
        $registerHistory->description   =   $description;
        $registerHistory->value         =   $amount;
        $registerHistory->save();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The cash has successfully been disbursed.' ),
            'data'      =>  [
                'register'  =>  $register,
                'history'   =>  $registerHistory
            ]
        ];
    }

    /**
     * @todo it might be relevant to put this into
     * a separate job
     * @param CashRegisterHistoryAfterCreatedEvent $event
     */
    public function updateRegisterAmount( CashRegisterHistoryAfterCreatedEvent $event )
    {
        $register       =   Register::find( $event->registerHistory->register_id );

        if ( $register instanceof Register && $register->status === Register::STATUS_OPENED ) {
            if( in_array( $event->registerHistory->action, RegisterHistory::IN_ACTIONS ) ) {
                $register->balance      +=  $event->registerHistory->value;
            } else if ( in_array( $event->registerHistory->action, RegisterHistory::OUT_ACTIONS ) ) {
                $register->balance      -=  $event->registerHistory->value;
            }

            $register->save();
        }
    }

    /**
     * @deprecated
     */
    public function increaseFromOrderPayment( OrderAfterPaymentCreatedEvent $event )
    {
        if ( $event->order->register_id !== null ) {
            $registerHistory                =   new RegisterHistory;
            $registerHistory->value         =   $event->orderPayment->value;
            $registerHistory->register_id   =   $event->order->register_id;
            $registerHistory->action        =   RegisterHistory::ACTION_SALE;
            $registerHistory->author        =   Auth::id();
            $registerHistory->saveQuietly();

            /**
             * @todo : not really proud of this. :'(    
             */
            $event  =   new CashRegisterHistoryAfterCreatedEvent( $registerHistory );

            $this->updateRegisterAmount( $event );
        }
    }

    /**
     * Listen for payment status changes event
     * that only occurs if the order is updated
     * and will update the register history accordingly.
     * @param OrderAfterPaymentStatusChangedEvent $event
     * @return void
     */
    public function increaseFromPaidOrder( OrderAfterPaymentStatusChangedEvent $event )
    {
        /**
         * If the payment status changed from
         * supported payment status to a "Paid" status.
         */
        if ( $event->order->register_id !== null && in_array( $event->previous, [
            Order::PAYMENT_DUE,
            Order::PAYMENT_HOLD,
            Order::PAYMENT_PARTIALLY,
            Order::PAYMENT_UNPAID,
        ] ) && $event->new === Order::PAYMENT_PAID ) {
            $registerHistory                =   new RegisterHistory;
            $registerHistory->value         =   $event->order->total;
            $registerHistory->register_id   =   $event->order->register_id;
            $registerHistory->action        =   RegisterHistory::ACTION_SALE;
            $registerHistory->author        =   Auth::id();
            $registerHistory->saveQuietly();

            $event  =   new CashRegisterHistoryAfterCreatedEvent( $registerHistory );

            $this->updateRegisterAmount( $event );
        }
    }

    /**
     * Listen to order created and
     * will update the cash register if any order
     * is marked as paid.
     * @param OrderAfterCreatedEvent|OrderAfterUpdatedEvent $event
     * @return void
     */
    public function increaseFromOrderCreatedEvent( $event )
    {
        /**
         * If the payment status changed from
         * supported payment status to a "Paid" status.
         */
        if ( $event->order->register_id !== null && $event->order->payment_status === Order::PAYMENT_PAID ) {
            $registerHistory                =   new RegisterHistory;
            $registerHistory->value         =   $event->order->total;
            $registerHistory->register_id   =   $event->order->register_id;
            $registerHistory->action        =   RegisterHistory::ACTION_SALE;
            $registerHistory->author        =   Auth::id();
            $registerHistory->saveQuietly();

            $event  =   new CashRegisterHistoryAfterCreatedEvent( $registerHistory );

            $this->updateRegisterAmount( $event );
        }
    }

    public function afterOrderRefunded( OrderRefundPaymentAfterCreatedEvent $event )
    {
        /**
         * the refund can't always be made from the register the order 
         * has been created. We need to consider the fact refund can't lead
         * to cash drawer disbursement.
         */        
    }

    /**
     * returns human readable labels
     * for all register actions.
     * @param string $label
     * @return string
     */
    public function getActionLabel( $label )
    {
        switch( $label ) {
            case RegisterHistory::ACTION_CASHING:
                return __( 'Cash In' );
            break;
            case RegisterHistory::ACTION_CASHOUT:
                return __( 'Cash Out' );
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
     * @param string $label
     * @return string
     */
    public function getRegisterStatusLabel( $label )
    {
        switch( $label ) {
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
     * Update the register with various details
     * @param Register $register
     * @return void
     */
    public function getRegisterDetails( Register $register )
    {
        $register->status_label         =   $this->getRegisterStatusLabel( $register->status );
        $register->opening_balance      =   0;
        $register->total_sale_amount    =   0;

        if ( $register->status === Register::STATUS_OPENED ) {
            $history                        =   $register->history()
                ->where( 'action', RegisterHistory::ACTION_OPENING )
                ->orderBy( 'id', 'desc' )->first();
            $register->opening_balance      =   $history->value;
            $register->total_sale_amount    =   Order::paid()
                ->where( 'register_id', $register->id )
                ->where( 'created_at', '>=', $history->created_at )
                ->sum( 'total' );
        }

        return $register;
    }

    /**
     * Will save the order total amount to the 
     * register every time the order is completely paid.
     * @param OrderAfterCreatedEvent|OrderAfterUpdatedEvent $event
     * @return void
     */
    public function recordPaidOrderAmount( $event )
    {
        if ( $event->order->payment_status === Order::PAYMENT_PAID ) {
            $register   =   Register::find( $event->order->register_id );

            if ( $register instanceof Register ) {
                $this->cashIn( $register, $event->order->total, __( 'Automatically recorded sale payment.' ) );
            }
        }
    }

    /**
     * Will issue an expense history for every
     * cashing out operation if an expense category is assigned
     * @param CashRegisterHistoryAfterCreatedEvent $event
     * @return void
     */
    public function issueExpenses( CashRegisterHistoryAfterCreatedEvent $event )
    {
        /**
         * @var ExpenseService
         */
        $expenseService     =   app()->make( ExpenseService::class );
        $cat_id             =   ns()->option->get( 'ns_pos_cashout_expense_category' );
        $expenseCategory    =   ExpenseCategory::find( $cat_id );

        if ( $expenseCategory instanceof ExpenseCategory && $event->registerHistory->action === RegisterHistory::ACTION_CASHOUT ) {
            /**
             * We simulate a created expense
             * that will be added to the expenses history
             * but it won't be persistent.
             */
            $expense                =   new Expense();
            $expense->name          =   $event->registerHistory->description ?: __( 'Cash out' );
            $expense->category_id   =   $expenseCategory->id;
            $expense->description   =   __( 'An automatically generated expense for cash-out operation.' );
            $expense->value         =   $event->registerHistory->value;
            $expense->author        =   Auth::id();
            $expense->id            =   0; // untracked expenses shouldn't be assigned
            $expense->active        =   true;

            $expenseService->triggerExpense( $expense );
        }
    }
}