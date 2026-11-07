<?php

namespace Tests\Feature;

use App\Exceptions\NotAllowedException;
use App\Models\Permission;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\CashRegistersService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterSessionAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $owner;

    private User $otherCashier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::namespace( Role::STORECASHIER )->addPermissions( Permission::namespace( 'nexopos.use.registers' ) );
        $this->owner = $this->createCashier();
        $this->otherCashier = $this->createCashier();
        $this->owner->assignRole( Role::STORECASHIER );
        $this->otherCashier->assignRole( Role::STORECASHIER );
    }

    public static function registerActions(): array
    {
        return [
            'close alias' => [ 'close', RegisterHistory::ACTION_CLOSING, 0 ],
            'close constant' => [ RegisterHistory::ACTION_CLOSING, RegisterHistory::ACTION_CLOSING, 0 ],
            'cash in' => [ RegisterHistory::ACTION_CASHING, RegisterHistory::ACTION_CASHING, 110 ],
            'cash out' => [ RegisterHistory::ACTION_CASHOUT, RegisterHistory::ACTION_CASHOUT, 90 ],
        ];
    }

    #[DataProvider( 'registerActions' )]
    public function test_another_cashier_cannot_mutate_the_owners_register( string $action, string $historyAction, int $balance ): void
    {
        $register = $this->openRegister();
        $before = $register->getAttributes();
        $historyCount = $register->history()->count();
        Sanctum::actingAs( $this->otherCashier, ['*'] );
        $this->assertTrue( ns()->allowedTo( 'nexopos.use.registers' ) );

        $this->postJson( 'api/cash-registers/' . $action . '/' . $register->id, [ 'amount' => 10 ] )
            ->assertForbidden()
            ->assertJsonPath( 'message', 'Only the cashier who opened this register can perform this action.' );

        $this->assertSame( $before, $register->fresh()->getAttributes() );
        $this->assertSame( $historyCount, $register->history()->count() );
    }

    #[DataProvider( 'registerActions' )]
    public function test_owner_can_mutate_their_register( string $action, string $historyAction, int $balance ): void
    {
        $register = $this->openRegister();
        $this->postJson( 'api/cash-registers/' . $action . '/' . $register->id, [ 'amount' => 10 ] )
            ->assertOk()->assertJsonPath( 'status', 'success' );
        $register->refresh();
        $this->assertEquals( $balance, $register->balance );
        $this->assertSame( $historyAction === RegisterHistory::ACTION_CLOSING ? Register::STATUS_CLOSED : Register::STATUS_OPENED, $register->status );
        $this->assertEquals( $historyAction === RegisterHistory::ACTION_CLOSING ? null : $this->owner->id, $register->used_by );
        $history = $register->history()->latest( 'id' )->first();
        $this->assertSame( $historyAction, $history->action );
        $this->assertEquals( $this->owner->id, $history->author_id );
    }

    public static function serviceActions(): array
    {
        return [ [ 'closeRegister' ], [ 'cashIng' ], [ 'cashOut' ] ];
    }

    #[DataProvider( 'serviceActions' )]
    public function test_direct_service_calls_cannot_bypass_ownership( string $method ): void
    {
        $register = $this->openRegister();
        Sanctum::actingAs( $this->otherCashier, ['*'] );
        $this->expectException( NotAllowedException::class );
        app( CashRegistersService::class )->$method( $register, 10, null );
    }

    #[DataProvider( 'serviceActions' )]
    public function test_register_without_an_owner_cannot_be_mutated( string $method ): void
    {
        $register = $this->openRegister();
        $register->used_by = null;
        $register->save();
        $this->expectException( NotAllowedException::class );
        app( CashRegistersService::class )->$method( $register, 10, null );
    }

    public function test_owner_id_can_be_hydrated_as_a_string(): void
    {
        $register = $this->openRegister();
        $register->used_by = (string) $this->owner->id;
        app( CashRegistersService::class )->closeRegister( $register, 100, null );
        $this->assertSame( Register::STATUS_CLOSED, $register->fresh()->status );
    }

    private function createCashier(): User
    {
        $user = User::factory()->make( [ 'username' => fake()->unique()->userName(), 'active' => true ] );
        unset( $user->email_verified_at );
        User::withoutEvents( fn () => $user->save() );

        return $user;
    }

    private function openRegister(): Register
    {
        Sanctum::actingAs( $this->owner, ['*'] );
        $register = new Register;
        $register->name = 'Ownership regression test';
        $register->status = Register::STATUS_CLOSED;
        $register->author_id = $this->owner->id;
        $register->balance = 0;
        $register->save();
        $this->postJson( 'api/cash-registers/open/' . $register->id, [ 'amount' => 100 ] )->assertOk();

        return $register->fresh();
    }
}
