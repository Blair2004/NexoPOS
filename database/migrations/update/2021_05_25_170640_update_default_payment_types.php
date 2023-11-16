<?php

use App\Models\PaymentType;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $admin = Role::namespace( 'admin' )->users->first();

        $paymentType = new PaymentType;
        $paymentType->label = __( 'Cash' );
        $paymentType->identifier = 'cash-payment';
        $paymentType->readonly = true;
        $paymentType->author = $admin->id;
        $paymentType->save();

        $paymentType = new PaymentType;
        $paymentType->label = __( 'Bank Payment' );
        $paymentType->identifier = 'bank-payment';
        $paymentType->readonly = true;
        $paymentType->author = $admin->id;
        $paymentType->save();

        $paymentType = new PaymentType;
        $paymentType->label = __( 'Customer Account' );
        $paymentType->identifier = 'account-payment';
        $paymentType->readonly = true;
        $paymentType->author = $admin->id;
        $paymentType->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
