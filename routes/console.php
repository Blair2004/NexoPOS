<?php

use App\Models\Order;
use App\Models\Role;
use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Modules\NsGastro\Events\KitchenAfterUpdatedOrderEvent;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->describe('Display an inspiring quote');

Artisan::command('notify', function () {
    /**
     * @var NotificationService
     */
    $notificationService    =   app()->make( NotificationService::class );

    // $notificationService->create([
    //     'title'         =>  __( 'Unpaid Orders Turned Due' ),
    //     'identifier'    =>  '123456789',
    //     'url'           =>  ns()->route( 'ns.dashboard.orders' ),
    //     'description'   =>  sprintf( __( '%s order(s) either unpaid or partially paid has turned due. This occurs if none has been completed before the expected payment date.' ), 10 )
    // ])->dispatchForGroup([
    //     Role::namespace( 'admin' ),
    //     Role::namespace( 'nexopos.store.administrator' )
    // ]);

    $notificationService->deleteHavingIdentifier( '123456789' );

    // KitchenAfterUpdatedOrderEvent::dispatch( Order::first() );
})->describe('test notification');
