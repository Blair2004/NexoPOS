<?php

namespace App\Services;

use App\Classes\Hook;
use App\Models\Procurement;
use App\Models\Provider;
use Exception;
use Illuminate\Support\Facades\Auth;

class ProviderService
{
    public function get( $id = null )
    {
        return $id === null ? Provider::get() : Provider::find( $id );
    }

    /**
     * create a provider
     * using the provided informations
     *
     * @param array information to save
     * @return array response
     */
    public function create( $data )
    {
        $provider = new Provider;

        foreach ( $data as $field => $value ) {
            $provider->$field = $value;
        }

        $provider->author = Auth::id();
        $provider->save();

        return [
            'status' => 'success',
            'message' => __( 'The provider has been created.' ),
            'data' => compact( 'provider' ),
        ];
    }

    /**
     * Edit a specific provide using the
     * provided informations
     *
     * @param int provider id
     * @param array data to update
     * @return array response
     */
    public function edit( $id, $data )
    {
        $provider = Provider::find( $id );

        if ( $provider instanceof Provider ) {
            foreach ( $data as $field => $value ) {
                $provider->$field = $value;
            }

            $provider->author = Auth::id();
            $provider->save();

            return [
                'status' => 'success',
                'message' => __( 'The provider has been updated.' ),
                'data' => compact( 'provider' ),
            ];
        }

        throw new Exception( __( 'Unable to find the provider using the specified id.' ) );
    }

    /**
     * Delete the provider using the
     * specified id
     *
     * @param int provider id
     * @return array response
     */
    public function delete( $id )
    {
        $provider = Provider::findOrFail( $id );
        $provider->delete();

        return [
            'status' => 'success',
            'message' => __( 'The provider has been deleted.' ),
        ];
    }

    /**
     * list the procurements made for
     * the provider which id is specified
     *
     * @param int provider id
     * @return array
     */
    public function procurements( $provider_id )
    {
        $provider = Provider::find( $provider_id );

        if ( ! $provider instanceof Provider ) {
            throw new Exception( __( 'Unable to find the provider using the specified identifier.' ) );
        }

        return $provider->procurements;
    }

    /**
     * compute owned amount
     *
     * @param int provider id
     * @return array
     */
    public function computeSummary( Provider $provider )
    {
        try {
            $totalOwed = $provider->procurements()->where( 'payment_status', 'unpaid' )->sum( 'value' );
            $totalPaid = $provider->procurements()->where( 'payment_status', 'paid' )->sum( 'value' );

            $provider->amount_due = $totalOwed;
            $provider->amount_paid = $totalPaid;
            $provider->save();

            return [
                'status' => 'success',
                'message' => __( 'The provider account has been updated.' ),
            ];
        } catch ( Exception $exception ) {
            throw new Exception( sprintf( __( 'An error occurred: %s.' ), $exception->getMessage() ) );
        }
    }

    /**
     * Will return a human redale status
     *
     * @param  string $label
     * @return string
     */
    public function getDeliveryStatusLabel( $label )
    {
        switch ( $label ) {
            case Procurement::PENDING:
                $label = __( 'Pending' );
                break;
            case Procurement::DELIVERED:
                $label = __( 'Delivered' );
                break;
            case Procurement::STOCKED:
                $label = __( 'Stocked' );
                break;
            default:
                $label = Hook::filter( 'ns-delivery-status', $label );
                break;
        }

        return $label;
    }

    /**
     * Will return the payment status label
     *
     * @param  string $label
     * @return string
     */
    public function getPaymentStatusLabel( $label )
    {
        switch ( $label ) {
            case Procurement::PAYMENT_UNPAID:
                $label = __( 'Unpaid' );
                break;
            case Procurement::PAYMENT_PAID:
                $label = __( 'Paid' );
                break;
        }

        return $label;
    }

    /**
     * When a procurement is being edited
     * we'll consider editing the provide payments
     * to avoid having the payment made twice for the same procurement
     *
     * @return void
     */
    public function cancelPaymentForProcurement( Procurement $procurement )
    {
        $provider = Provider::find( $procurement->provider_id );

        if ( $provider instanceof Provider ) {
            if ( $procurement->payment_status === 'paid' ) {
                $provider->amount_paid -= $procurement->cost;
            } else {
                $provider->amount_due -= $procurement->cost;
            }

            $provider->save();
        }

        return [
            'status' => 'succecss',
            'message' => __( 'The procurement payment has been deducted.' ),
        ];
    }
}
