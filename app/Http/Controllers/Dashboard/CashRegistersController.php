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
use Exception;
use Illuminate\Support\Facades\Auth;

class CashRegistersController extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
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
}

