<?php

use App\Models\PaymentType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Register the PayTheFly payment type so it appears in the POS.
     */
    public function up(): void
    {
        if ( ! PaymentType::where( 'identifier', 'paythefly-crypto' )->exists() ) {
            $paymentType              = new PaymentType;
            $paymentType->label       = 'PayTheFly Crypto';
            $paymentType->identifier  = 'paythefly-crypto';
            $paymentType->description = 'Cryptocurrency payment via PayTheFly (BSC / TRON)';
            $paymentType->active      = true;
            $paymentType->readonly    = true;
            $paymentType->author      = 1; // system admin
            $paymentType->save();
        }
    }

    /**
     * Remove the PayTheFly payment type.
     */
    public function down(): void
    {
        PaymentType::where( 'identifier', 'paythefly-crypto' )->delete();
    }
};
