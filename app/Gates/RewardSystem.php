<?php
namespace App\Gates;

use Illuminate\Support\Facades\Auth;

class RewardSystem
{
    public function create()
    {
        return Auth::user()->allowedTo( 'nexopos.create.reward-system' );
    }
}