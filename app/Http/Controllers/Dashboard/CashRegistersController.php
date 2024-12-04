<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Classes\Hook;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

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
        } elseif ( $action === RegisterHistory::ACTION_CASHING ) {
            return $this->registersService->cashIng(
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

    private function getRightOrderPaymentLabel( RegisterHistory $history )
    {
        if ( $history->action === RegisterHistory::ACTION_ORDER_PAYMENT ) {
            return sprintf(
                __( 'Payment %s on %s' ),
                $history->payment_label,
                $history->order->code
            );
        } elseif ( $history->action === RegisterHistory::ACTION_ORDER_CHANGE ) {
            return sprintf(
                __( 'Change %s on %s' ),
                $history->payment_label,
                $history->order->code
            );
        }

        return sprintf(
            __( 'Unknown action %s' ),
            $history->action
        );
    }

    public function getSessionHistory( Register $register )
    {
        if ( $register->status === Register::STATUS_OPENED ) {
            $lastOpening = $register->history()
                ->where( 'action', RegisterHistory::ACTION_OPENING )
                ->orderBy( Hook::filter( 'ns-table-name', 'nexopos_registers_history' ) . '.id', 'desc' )
                ->first();

            if ( $lastOpening instanceof RegisterHistory ) {
                /**
                 * @var Builder
                 */
                $historyRequest = $register->history()
                    ->select( [
                        'nexopos_registers_history.*',
                        'nexopos_registers_history.description as description',
                        'nexopos_payments_types.label as payment_label',
                    ] )
                    ->with( 'order' )
                    ->leftJoin( 'nexopos_payments_types', 'nexopos_registers_history.payment_type_id', '=', 'nexopos_payments_types.id' )
                    ->where( 'nexopos_registers_history.id', '>=', $lastOpening->id );

                $history = $historyRequest->get();

                $history->each( function ( RegisterHistory $session ) {
                    switch ( $session->action ) {
                        case RegisterHistory::ACTION_CASHING:
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
                        case RegisterHistory::ACTION_ORDER_PAYMENT:
                        case RegisterHistory::ACTION_ORDER_CHANGE:
                            // @todo it might be a custom label based on the payment
                            $session->label = $this->getRightOrderPaymentLabel( $session );
                            break;
                        case RegisterHistory::ACTION_REFUND:
                            $session->label = __( 'Refund' );
                            break;
                    }
                } );

                $totalDisbursement = $history->where( 'action', RegisterHistory::ACTION_CASHOUT )->sum( 'value' );
                $totalCashIn = $history->where( 'action', RegisterHistory::ACTION_CASHING )->sum( 'value' );

                $cashPayment = PaymentType::identifier( OrderPayment::PAYMENT_CASH )->first();
                $totalOpening = $lastOpening->value;

                // we might need to pull based on payment type
                // @todo this is no longer correct
                $totalCashPayment = $history->where( 'action', RegisterHistory::ACTION_ORDER_PAYMENT )
                    ->where( 'payment_type_id', $cashPayment->id ?? 0 )
                    ->sum( 'value' );

                $totalCashChange = $history->where( 'action', RegisterHistory::ACTION_ORDER_CHANGE )->sum( 'value' );

                $totalPaymentTypeSummary = $historyRequest
                    ->whereIn( 'action', [
                        RegisterHistory::ACTION_ORDER_PAYMENT,
                    ] )
                    ->select( [
                        DB::raw( 'SUM(value) as value' ),
                        'nexopos_payments_types.label as label',
                        'action',
                        'nexopos_registers_history.description as description',
                    ] )
                    ->groupBy( [ 'action', 'nexopos_payments_types.label', 'description' ] )
                    ->get()
                    ->map( function ( $group ) {
                        $color = 'info';

                        if ( $group->action === RegisterHistory::ACTION_ORDER_PAYMENT ) {
                            $color = 'success';
                        }

                        return [
                            'label' => sprintf( __( 'Total %s' ), $group->label ),
                            'value' => $group->value,
                            'color' => $color,
                        ];
                    } );

                $summary = [
                    [
                        'label' => __( 'Initial Balance' ),
                        'value' => $totalOpening,
                        'color' => 'info',
                    ],
                    ...$totalPaymentTypeSummary,
                    [
                        'label' => __( 'Total Change' ),
                        'value' => $totalCashChange,
                        'color' => 'warning',
                    ],
                    [
                        'label' => __( 'On Hand' ),
                        'value' => ns()->currency->define( $totalOpening )
                            ->additionateBy( $totalCashPayment )
                            ->additionateBy( $totalCashIn )
                            ->subtractBy(
                                ns()->currency->define( $totalCashChange )
                                    ->additionateBy( $totalDisbursement )
                                    ->toFloat()
                            )->toFloat(),
                        'color' => 'info',
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

    public function getRegisterZReport( Register $register )
    {
        $data = $this->registersService->getZReport( $register );

        /**
         * @var mixed register
         * @var mixed opening
         * @var mixed closing
         * @var mixed histories
         * @var mixed orders
         * @var mixed openingBalance
         * @var mixed closingBalance
         * @var mixed difference
         * @var mixed totalGrossSales
         * @var mixed totalDiscount
         * @var mixed total
         * @var mixed unitProductCategories
         * @var mixed user
         */

        return View::make( 'pages.dashboard.orders.templates.z-report', (array) $data );
    }
}
