<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\Validation;
use Illuminate\Support\Facades\Validator;
use App\Fields\ProviderFields;
use App\Http\Controllers\DashboardController;
use App\Models\Provider;
use App\Services\Options;
use App\Services\ProviderService;
use Illuminate\Validation\ValidationException;


class ProvidersController extends DashboardController
{
    public function __construct( 
        ProviderService $providerService,
        Options $options,
        Validation $validation
    ) {
        parent::__construct();

        $this->options              =   $options;
        $this->providerService      =   $providerService;
        $this->validation           =   $validation;        
    }

    /**
     * Retreive the provider list
     * @return array providers
     */
    public function list()
    {
        return $this->providerService->get();
    }

    public function listProviders()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.providers' ),
            'title'         =>  __( 'Providers' ),
            'description'   =>  __( 'List of registered providers' ),
            'createUrl'     =>  ns()->url( '/dashboard/providers/create' ),
        ]);
    }

    public function createProvider()
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.providers/form-config' ),
            'submitUrl'     =>  ns()->url( '/api/nexopos/v4/crud/ns.providers' ),
            'title'         =>  __( 'Create A Provider' ),
            'description'   =>  __( 'Add a new provider to the system' ),
            'returnUrl'     =>  ns()->url( '/dashboard/providers' ),
        ]);
    }

    public function editProvider( Provider $provider )
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'src'           =>  ns()->url( '/api/nexopos/v4/crud/ns.providers/form-config/' . $provider->id ),
            'submitUrl'     =>  ns()->url( '/api/nexopos/v4/crud/ns.providers/' . $provider->id ),
            'title'         =>  __( 'Edit Provider' ),
            'submitMethod'  =>  'PUT',
            'description'   =>  __( 'Modify an existing provider' ),
            'returnUrl'     =>  ns()->url( '/dashboard/providers' ),
        ]);
    }

    /**
     * controller to create
     * a single provider
     * @param Request request
     * @return array
     */
    public function create( Request $request )
    {
        $validationResponse     =   Validator::make( 
            $request->all(), 
            $this->validation->from( ProviderFields::class )
                ->extract( 'get' )
        );

        if ( $validationResponse->fails() ) {
            throw new ValidationException( $validationResponse, __( 'The form contains one or more errors.' ) );
        }

        return $this->providerService->create( $request->only([
            'name',
            'surname',
            'email', 
            'address_1',
            'address_2',
            'description'
        ]));
    }

    /**
     * Edit an existing provider
     * @param int provider id
     * @param Request
     * @return array response
     */
    public function edit( $id, Request $request )
    {
        $validationResponse     =   Validator::make( 
            $request->all(), 
            $this->validation->from( ProviderFields::class )
                ->extract( 
                    'get',
                    $this->providerService->get( $id )
                )
        );

        if ( $validationResponse->fails() ) {
            throw new ValidationException( $validationResponse, __( 'The form contains one or more errors.' ) );
        }

        return $this->providerService->edit( $id, $request->only([
            'name',
            'surname',
            'email', 
            'address_1',
            'address_2',
            'description'
        ]));
    }

    public function providerProcurements( $provider_id )
    {
        return $this->providerService->procurements( $provider_id );
    }

    public function deleteProvider( $id )
    {
        return $this->providerService->delete( $id );   
    }
}

