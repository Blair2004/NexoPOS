<?php
namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Provider;
use Tendoo\Core\Exceptions\NotFoundException;

class ProviderService
{
    public function get( $id = null )
    {
        return $id === null ? Provider::get() : Provider::find( $id );
    }

    /**
     * create a provider
     * using the provided informations
     * @param array information to save
     * @return array response
     */
    public function create( $data )
    {
        $provider       =   new Provider;

        foreach( $data as $field => $value )
        {
            $provider->$field       =   $value;
        }

        $provider->author       =   Auth::id();
        $provider->save();

        return [
            'status'    =>      'success',
            'message'   =>      __( 'The provider has been created.' ),
            'data'      =>      compact( 'provider' )
        ];
    }

    /**
     * Edit a specific provide using the 
     * provided informations
     * @param int provider id
     * @param array data to update
     * @return array response
     */
    public function edit( $id, $data )
    {
        $provider   =   Provider::find( $id );

        if ( $provider instanceof Provider ) {
            foreach( $data as $field => $value ) {
                $provider->$field   =   $value;
            }

            $provider->author       =   Auth::id();
            $provider->save();
            
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The provider has been updated.' ),
                'data'      =>  compact( 'provider' )
            ];
        }
        
        throw new NotFoundException([
            'status'    =>  'failed',
            'message'   =>  __( 'Unable to find the provider using the specified id.' )
        ]);
    }

    /**
     * Delete the provider using the
     * specified id
     * @param int provider id
     * @return array response
     */
    public function delete( $id )
    {
        try {
            $provider   =   Provider::findOrFail( $id );
            $provider->delete();

            return [
                'status'    =>  'success',
                'message'   =>  __( 'The provider has been deleted.' )
            ];

        } catch( NotFoundException $exception ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to delete. The specified id is not valid or the provider with the same id doesn\'t exists.' )
            ]);
        }
    }

    /**
     * list the procurements made for
     * the provider which id is specified
     * @param int provider id
     * @return array
     */
    public function procurements( $provider_id )
    {
        $provider   =   Provider::find( $provider_id );

        if ( ! $provider instanceof Provider ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the provider using the specified identifier.' )
            ]);
        }

        return $provider->procurements;
    }

    /**
     * compute owned amount
     * @param int provider id
     * @return array
     */
    public function computeOwned( $provider_id )
    {
        try {
            $provider   =   Provider::find( $id );

            $owned      =   collect( $provider->procurements )->sum( function( $procurement ) {
                if ( $procurement->status === 'unpaid' ) {
                    return $procurement->value;
                }
                return 0;
            });

            $provider->owned_amount  =   $owned;
            $provider->save();

        } catch( NotFoundException $exception ) {
            throw new NotFoundException([
                'status'    =>  'failed',
                'message'   =>  __( 'Unable to find the provider using the specified identifier.' )
            ]);
        }
    }
}