<?php
namespace App\Services;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderRefundPaymentAfterCreatedEvent;
use App\Exceptions\NotAllowedException;
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

        $registerHistory            =   new RegisterHistory;
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

        $registerHistory    =   new RegisterHistory;
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
            } else {
                $register->balance      -=  $event->registerHistory->value;
            }

            $register->save();
        }
    }

    public function increaseFromOrderPayment( OrderAfterPaymentCreatedEvent $event )
    {
        if ( $event->order->register_id !== null ) {
            $registerHistory                =   new RegisterHistory;
            $registerHistory->value         =   $event->orderPayment->value;
            $registerHistory->register_id   =   $event->order->register_id;
            $registerHistory->action        =   RegisterHistory::ACTION_SALE;
            $registerHistory->author        =   Auth::id();
            $registerHistory->save();
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
}