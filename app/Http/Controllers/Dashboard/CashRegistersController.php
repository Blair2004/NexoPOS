<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\RegisterCrud;
use App\Crud\RegisterHistoryCrud;
use App\Exceptions\NotAllowedException;
use App\Http\Controllers\DashboardController;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Services\CashRegistersService;
use App\Services\DateService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegistersController extends DashboardController
{
    public function __construct(
        protected CashRegistersService $registersService,
        protected DateService $dateService
    ) {
        // ...
    }

    public function listRegisters()
    {
        return RegisterCrud::table();
    }

    public function createRegister()
    {
        return RegisterCrud::form();
    }

    public function editRegister( Register $register )
    {
        if ( $register->status === Register::STATUS_OPENED ) {
            throw new NotAllowedException( __( 'Unable to edit a register that is currently in use' ) );
        }

        return RegisterCrud::form( $register );
    }

    public function getRegisters( $register_id = null )
    {
        if ( $register_id !== null ) {
            $register = Register::findOrFail( $register_id );
            $this->registersService->getRegisterDetails( $register );

            return $register;
        }

        return Register::get()->map( function ( $register ) {
            $this->registersService->getRegisterDetails( $register );

            return $register;
        } );
    }

    /**
     * @todo check repetitive transactions
     */
    public function performAction( Request $request, $action, Register $register )
    {
        if ( $action === 'open' ) {
            return $this->registersService->openRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } elseif ( $action === RegisterHistory::ACTION_OPENING ) {
            return $this->registersService->openRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } elseif ( $action === 'close' ) {
            return $this->registersService->closeRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } elseif ( $action === RegisterHistory::ACTION_CLOSING ) {
            return $this->registersService->closeRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } elseif ( $action === RegisterHistory::ACTION_CASHIN ) {
            return $this->registersService->cashIn(
                register: $register,
                amount: $request->input( 'amount' ),
                description: $request->input( 'description' )
            );
        } elseif ( $action === RegisterHistory::ACTION_CASHOUT ) {
            return $this->registersService->cashOut(
                register: $register,
                amount: $request->input( 'amount' ),
                description: $request->input( 'description' )
            );
        }
    }

    public function getUsedRegister()
    {
        $register = Register::opened()
            ->usedBy( Auth::id() )
            ->first();

        if ( ! $register instanceof Register ) {
            throw new NotAllowedException( __( 'No register has been opened by the logged user.' ) );
        }

        return [
            'status' => 'success',
            'message' => __( 'The register is opened.' ),
            'data' => compact( 'register' ),
        ];
    }

    public function getSessionHistory( Register $register )
    {
        if ( $register->status === Register::STATUS_OPENED ) {
            $lastOpening = $register->history()
                ->where( 'action', RegisterHistory::ACTION_OPENING )
                ->orderBy( 'nexopos_registers_history.id', 'desc' )
                ->first();

            if ( $lastOpening instanceof RegisterHistory ) {
                /**
                 * @var Builder
                 */
                $historyRequest = $register->history()
                    ->select([
                        '*',
                        'nexopos_registers_history.description as description',
                    ])
                    ->leftJoin( 'nexopos_payments_types', 'nexopos_registers_history.payment_type_id', '=', 'nexopos_payments_types.id' )
                    ->where( 'nexopos_registers_history.id', '>=', $lastOpening->id );

                $history =  $historyRequest->get();
                $history->each( function ( $session ) {
                    switch ( $session->action ) {
                        case RegisterHistory::ACTION_CASHIN:
                            $session->label = __( 'Cash In' );
                            break;
                        case RegisterHistory::ACTION_CASHOUT:
                            $session->label = __( 'Cash Out' );
                            break;
                        case RegisterHistory::ACTION_CLOSING:
                            $session->label = __( 'Closing' );
                            break;
                        case RegisterHistory::ACTION_OPENING:
                            $session->label = __( 'Opening' );
                            break;
                        case RegisterHistory::ACTION_SALE:
                            $session->label = __( 'Sale' );
                            break;
                        case RegisterHistory::ACTION_REFUND:
                            $session->label = __( 'Refund' );
                            break;
                    }
                } );

                $totalDisbursement  =   $history->where( 'action', RegisterHistory::ACTION_CASHOUT )->sum( 'value' );
                $totalCashIn        =   $history->where( 'action', RegisterHistory::ACTION_CASHIN )->sum( 'value' );

                $cashPayment    =   PaymentType::identifier( OrderPayment::PAYMENT_CASH )->first();
                $totalOpening   =   $lastOpening->value;
                $totalCashPayment   =   $history->where( 'action', RegisterHistory::ACTION_SALE )
                    ->where( 'payment_type_id', $cashPayment->id )
                    ->sum( 'value' );
                    
                $totalCashChange    =   $history->where( 'action', RegisterHistory::ACTION_CASH_CHANGE )->sum( 'value' );

                $totalPaymentTypeSummary    =   $historyRequest
                    ->whereIn( 'action', [
                        RegisterHistory::ACTION_SALE,
                        RegisterHistory::ACTION_CASH_CHANGE
                    ])
                    ->select([
                        DB::raw( 'SUM(value) as value' ),
                        'nexopos_payments_types.label as label',
                        'action',
                        'nexopos_registers_history.description as description',
                    ])
                    ->groupBy([ 'action', 'nexopos_payments_types.label', 'description' ])
                    ->get()
                    ->map( function ( $group ) {
                        $color = 'info';

                        if ( $group->action === RegisterHistory::ACTION_CASH_CHANGE ) {
                            $label  =   __( 'Cash Change' );
                            $color  =   'error';
                        } else if ( $group->action === RegisterHistory::ACTION_SALE ) {
                            $color  =   'success';
                        }
                        
                        return [
                            'label'         =>  sprintf( __( 'Total %s' ), $group->label ?: $label ),
                            'value'         =>  $group->value,
                            'color'         =>  $color
                        ];
                    } );

                $summary    =   [
                    [
                        'label'         =>  __( 'Initial Balance' ),
                        'value'         =>  $totalOpening, 
                        'color'         =>  'warning',
                    ], 
                    ...$totalPaymentTypeSummary,
                    [
                        'label'         =>  __( 'On Hand' ),
                        'value'         =>  ns()->currency->define( $totalOpening )
                            ->additionateBy( $totalCashPayment )
                            ->additionateBy( $totalCashIn )
                            ->subtractBy( 
                                ns()->currency->define( $totalCashChange )
                                    ->additionateBy( $totalDisbursement )
                                    ->toFloat()
                            )->toFloat(), 
                        'color'         =>  'info',
                    ], 
                ];

                return compact(
                    'history',
                    'summary'
                );
            }

            throw new NotAllowedException( __( 'The register doesn\'t have an history.' ) );
        }

        throw new NotAllowedException( __( 'Unable to check a register session history if it\'s closed.' ) );
    }

    /**
     * returns the cahs register instance
     *
     * @return string
     */
    public function getRegisterHistory( Register $register )
    {
        return RegisterHistoryCrud::table( [
            'title' => sprintf( __( 'Register History For : %s' ), $register->name ),
            'queryParams' => [
                'register_id' => $register->id,
            ],
        ] );
    }
}
