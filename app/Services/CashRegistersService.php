<?php
namespace App\Services;

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

        $registerHistory    =   new RegisterHistory;
        $registerHistory->register_id   =   $register->id;
        $registerHistory->action        =   RegisterHistory::ACTION_OPENING;
        $registerHistory->author        =   Auth::id();
        $registerHistory->description   =   $description;
        $registerHistory->value         =   $amount;
        $registerHistory->save();

        $register->status   =  Register::STATUS_OPENED;
        $register->used_by  =   Auth::id();
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
}