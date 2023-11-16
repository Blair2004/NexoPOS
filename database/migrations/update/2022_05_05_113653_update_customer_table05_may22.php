<?php

use App\Classes\Schema;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table( 'nexopos_customers_account_history', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_customers_account_history', 'previous_amount' ) ) {
                $table->float( 'previous_amount' )->default(0);
            }

            if ( ! Schema::hasColumn( 'nexopos_customers_account_history', 'next_amount' ) ) {
                $table->float( 'next_amount' )->default(0);
            }
        });

        Customer::get()->each( function ( $customer ) {
            $customer->account_history()
                ->orderBy( 'created_at', 'desc' )
                ->get()
                ->each( function ( $walletHistory ) {
                    $this->handleWalletHistory( $walletHistory );
                });
        });
    }

    protected function handleWalletHistory( $history )
    {
        $beforeRecord = CustomerAccountHistory::where( 'id', '<', $history->id )
            ->where( 'customer_id', $history->customer_id )
            ->orderBy( 'id', 'desc' )
            ->first();

        $previousNextAmount = $beforeRecord instanceof CustomerAccountHistory ? $beforeRecord->next_amount : 0;

        switch ( $history->operation ) {
            case CustomerAccountHistory::OPERATION_ADD:
            case CustomerAccountHistory::OPERATION_REFUND:
                $history->previous_amount = $previousNextAmount;
                $history->next_amount = $previousNextAmount + $history->amount;
                break;
            case CustomerAccountHistory::OPERATION_PAYMENT:
            case CustomerAccountHistory::OPERATION_DEDUCT:
                $history->previous_amount = $previousNextAmount;
                $history->next_amount = $previousNextAmount - $history->amount;
                break;
        }

        $history->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table( 'nexopos_customers_account_history', function ( Blueprint $table ) {
            if ( Schema::hasColumn( 'nexopos_customers_account_history', 'previous_amount' ) ) {
                $table->dropColumn( 'previous_amount' );
            }
            if ( Schema::hasColumn( 'nexopos_customers_account_history', 'next_amount' ) ) {
                $table->dropColumn( 'next_amount' );
            }
        });
    }
};
