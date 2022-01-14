<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\RegisterCrud;
use App\Crud\RegisterHistoryCrud;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Services\CashRegistersService;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CashRegistersController extends DashboardController
{
    /**
     * @var CashRegistersService
     */
    protected $registersService;

    public function __construct(
        CashRegistersService $registersService
    )
    {
        parent::__construct();
        $this->registersService     =   $registersService;
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
            $register   =   Register::findOrFail( $register_id );
            $this->registersService->getRegisterDetails( $register );
            return $register;
        }

        return Register::get()->map( function( $register ) {
            $this->registersService->getRegisterDetails( $register );
            return $register;
        });
    }

    public function performAction( Request $request, $action, Register $register ) {
        if ( $action === 'open' ) {
            return $this->registersService->openRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } else if ( $action === RegisterHistory::ACTION_OPENING ) {
            return $this->registersService->openRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } else if( $action === 'close' ) {
            return $this->registersService->closeRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } else if( $action === RegisterHistory::ACTION_CLOSING ) {
            return $this->registersService->closeRegister(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } else if( $action === RegisterHistory::ACTION_CASHING ) {
            return $this->registersService->cashIn(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        } else if( $action === RegisterHistory::ACTION_CASHOUT ) {
            return $this->registersService->cashOut(
                $register,
                $request->input( 'amount' ),
                $request->input( 'description' )
            );
        }
    }

    public function getUsedRegister()
    {
        $register   =   Register::opened()
            ->usedBy( Auth::id() )
            ->first();

        if ( ! $register instanceof Register ) {
            throw new Exception( __( 'No register has been opened by the logged user.' ) );
        }
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The register is opened.' ),
            'data'      =>  compact( 'register' )
        ];
    }

    public function getSessionHistory( Register $register )
    {
        if ( $register->status === Register::STATUS_OPENED ) {
            $lastOpening    =   $register->history()
                ->where( 'action', RegisterHistory::ACTION_OPENING )
                ->orderBy( 'id', 'desc' )
                ->first();

            if ( $lastOpening instanceof RegisterHistory ) {
                /**
                 * @var Collection
                 */
                $actions        =   $register->history()
                    ->where( 'id', '>=', $lastOpening->id )
                    ->get();

                $actions->each( function( $session ) {
                    switch( $session->action ) {
                        case RegisterHistory::ACTION_CASHING : 
                            $session->label     =   __( 'Cash In' );
                        break;
                        case RegisterHistory::ACTION_CASHOUT : 
                            $session->label     =   __( 'Cash Out' );
                        break;
                        case RegisterHistory::ACTION_CLOSING : 
                            $session->label     =   __( 'Closing' );
                        break;
                        case RegisterHistory::ACTION_OPENING : 
                            $session->label     =   __( 'Opening' );
                        break;
                        case RegisterHistory::ACTION_SALE : 
                            $session->label     =   __( 'Sale' );
                        break;
                        case RegisterHistory::ACTION_REFUND : 
                            $session->label     =   __( 'Refund' );
                        break;
                    }
                });
    
                return $actions;
            }

            throw new NotAllowedException( __( 'The register doesn\'t have an history.' ) );
        }

        throw new NotAllowedException( __( 'Unable to check a register session history if it\'s closed.' ) );
    }

    /**
     * returns the cahs register instance
     * @param Register $register
     * @return string
     */
    public function getRegisterHistory( Register $register )
    {
        return RegisterHistoryCrud::table([
            'title'         =>  sprintf( __( 'Register History For : %s' ), $register->name ),
            'queryParams'   =>  [
                'register_id'   =>  $register->id
            ]
        ]);
    }
}

