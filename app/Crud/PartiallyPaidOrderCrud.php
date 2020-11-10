<?php
namespace App\Crud;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use App\Exceptions\NotAllowedException;
use App\Models\User;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Order;

class PartiallyPaidOrderCrud extends HoldOrderCrud
{
    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function hook( $query )
    {
        $query->orderBy( 'created_at', 'desc' );
        $query->where( 'payment_status', Order::PAYMENT_PARTIALLY );
    }
}