<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\Role;
use App\Services\CashRegistersService;
use App\Services\TestService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateOrderOnRegister extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_order_on_register()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $cashRegister   =   Register::first();
        $previousValue  =   $cashRegister->balance;

        /**
         * @var CashRegistersService
         */
        $cashRegisterService    =   app()->make( CashRegistersService::class );

        /**
         * Just in case it's opened
         */
        try {
            $cashRegisterService->closeRegister( $cashRegister, 0, __( 'Attempt closing' ) );
        } catch( NotAllowedException $exception ) {
            // it's probably not opened, let's proceed...
        }

        $cashRegisterService->openRegister( $cashRegister, 100, __( 'Opening the cash register' ) );
        
        /**
         * Step 1 : let's prepare the order
         * before submitting that.
         */
        $this->registerOrderForCashRegister( $cashRegister ); 

        /**
         * between each operation
         * we need to refresh the cash register
         */
        $cashRegister->refresh();

        $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );
        
        /**
         * Step 2 : disburse (cash-out) some cash
         * from the provided register
         */
        $this->disburseCashFromRegister( $cashRegister, $cashRegisterService );
        
        /**
         * between each operation
         * we need to refresh the cash register
         */
        $cashRegister->refresh();

        $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );

        /**
         * Step 3 : cash in some cash
         */
        $this->cashInOnRegister( $cashRegister, $cashRegisterService );

        /**
         * We neet to refresh the register
         * to make sure it has the updated values.
         */
        $cashRegister->refresh();

        $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );

        /**
         * Let's initialize the total transactions 
         */
        $totalTransactions      =   0;

        /**
         * last time the cash register has opened
         */
        $opening    =   RegisterHistory::action( RegisterHistory::ACTION_OPENING )->orderBy( 'id', 'desc' )->first();

        /**
         * We'll start by computing orders
         */
        $openingBalance         =  ( float ) $opening->value;

        $totalCashing           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CASHING )->sum( 'value' );

        $totalSales             =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_SALE )->sum( 'value' );

        $totalClosing           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CLOSING )->sum( 'value' );

        $totalCashOut           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CASHOUT )->sum( 'value' );

        $totalRefunds           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_REFUND )->sum( 'value' );

        $totalTransactions      =   ( $openingBalance + $totalCashing + $totalSales ) - ( $totalClosing + $totalRefunds + $totalCashOut );

        $this->assertEquals( $cashRegister->balance, $totalTransactions, __( 'The transaction aren\'t reflected on the register balance' ) );
    }

    private function registerOrderForCashRegister( Register $cashRegister )
    {
        /**
         * @var TestService
         */
        $testService    =   app()->make( TestService::class );

        $orderDetails   =   $testService->prepareOrder( ns()->date->now(), [
            'register_id'   =>  $cashRegister->id
        ]);

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );
        
        $response->assertStatus( 200 );
    }

    /**
     * Will disburse the cash register
     * @param Register $cashRegister
     * @param CashRegistersService $cashRegisterService
     * @return void
     */
    private function disburseCashFromRegister( Register $cashRegister, CashRegistersService $cashRegistersService )
    {
        $cashRegistersService->cashOut( $cashRegister, $cashRegister->balance / 1.5, __( 'Test disbursing the cash register' ) );
    }

    /**
     * Will disburse the cash register
     * @param Register $cashRegister
     * @param CashRegistersService $cashRegisterService
     * @return void
     */
    private function cashInOnRegister( Register $cashRegister, CashRegistersService $cashRegistersService )
    {
        $cashRegistersService->cashIn( $cashRegister, ( $cashRegister->balance / 2 ), __( 'Test disbursing the cash register' ) );
    }
}
