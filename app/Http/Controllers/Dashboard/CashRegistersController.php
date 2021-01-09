<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Crud\RegisterCrud;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\Register;
use App\Services\CashRegistersService;
use Exception;
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
            return Register::findOrFail( $register_id );
        }

        return Register::get();
    }

    public function openRegister( Request $request, Register $register ) {
        return $this->registersService->openRegister(
            $register,
            $request->input( 'amount' ),
            $request->input( 'description' )
        );
    }

    public function getUsedRegister()
    {
        $register   =   Register::usedBy( Auth::id() )->first();

        if ( ! $register instanceof Register ) {
            throw new Exception( __( 'No register has been opened by the logged user.' ) );
        }
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'The register is opened.' ),
            'data'      =>  compact( 'register' )
        ];
    }
}

