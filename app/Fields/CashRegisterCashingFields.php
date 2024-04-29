<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Models\TransactionAccount;
use App\Services\FieldsService;

class CashRegisterCashingFields extends FieldsService
{
    protected static $identifier = 'ns.cash-registers-cashing';

    public function get()
    {
        $fields = Hook::filter( 'ns-cash-register-cashing-fields', [
            [
                'label' => __( 'Amount' ),
                'description' => __( 'define the amount of the transaction.' ),
                'validation' => 'required',
                'name' => 'amount',
                'type' => 'hidden',
            ], [
                'label' => __( 'Account' ),
                'description' => __( 'Assign the transaction to an account.' ),
                'name' => 'transaction_account_id',
                'validation' => 'required',
                'options'   =>  TransactionAccount::credit()->where( function( $query ) {
                    $allowedAccount     =   ns()->option->get( 'ns_accounting_cashin_accounts' );
                    if ( ! empty( $allowedAccount ) ) {
                        $query->whereIn( 'id', $allowedAccount );
                    }
                })->get()->map( function ( $account ) {
                    return [
                        'label' => $account->name,
                        'value' => $account->id,
                    ];
                }),
                'type' => 'search-select',
                'value' =>  is_array( ns()->option->get( 'ns_accounting_cashin_accounts' ) ) ? ns()->option->get( 'ns_accounting_cashin_accounts' )[0] : ''
            ], [
                'label' => __( 'Description' ),
                'description' => __( 'Further observation while proceeding.' ),
                'name' => 'description',
                'type' => 'textarea',
            ],
        ] );

        return $fields;
    }
}
