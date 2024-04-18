<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\ProviderCrud;
use App\Crud\ProviderProcurementsCrud;
use App\Crud\ProviderProductsCrud;
use App\Http\Controllers\DashboardController;
use App\Models\Provider;
use App\Services\DateService;
use App\Services\Options;
use App\Services\ProviderService;
use App\Services\Validation;

class ProvidersController extends DashboardController
{
    public function __construct(
        protected ProviderService $providerService,
        protected Options $options,
        protected Validation $validation,
        protected DateService $dateService
    ) {
        // ...
    }

    /**
     * Retreive the provider list
     *
     * @return array providers
     */
    public function list()
    {
        return $this->providerService->get();
    }

    public function listProviders()
    {
        return ProviderCrud::table();
    }

    public function createProvider()
    {
        return ProviderCrud::form();
    }

    public function editProvider( Provider $provider )
    {
        return ProviderCrud::form( $provider );
    }

    public function providerProcurements( $provider_id )
    {
        return $this->providerService->procurements( $provider_id );
    }

    public function deleteProvider( $id )
    {
        return $this->providerService->delete( $id );
    }

    /**
     * Will return the list of procurements
     * made by the provider
     *
     * @return string
     */
    public function listProvidersProcurements( Provider $provider )
    {
        return ProviderProcurementsCrud::table( [
            'queryParams' => [
                'provider_id' => $provider->id,
            ],
            'title' => sprintf(
                __( 'Procurements by "%s"' ),
                $provider->first_name
            ),
        ] );
    }

    /**
     * Will list all products
     * provided by that provider
     *
     * @return array
     */
    public function listProvidersProducts( Provider $provider )
    {
        $procurements = $provider
            ->procurements()
            ->get( 'id' )
            ->map( fn( $procurement ) => $procurement->id )
            ->toArray();

        return ProviderProductsCrud::table( [
            'title' => sprintf( __( '%s\'s Products' ), $provider->name ),
            'queryParams' => [
                'procurements' => $procurements,
            ],
        ] );
    }
}
