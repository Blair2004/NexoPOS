<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Models\Order;
use App\Models\Customer;

use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use App\Models\CustomerAddress;
use App\Services\CustomerService;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

use App\Http\Controllers\DashboardController;
use Tendoo\Core\Exceptions\NotFoundException;
use Tendoo\Core\Exceptions\NotAllowedException;

class CustomersController extends DashboardController
{
    public function __construct(
        CustomerService $customerService
    )
    {
        parent::__construct();
        $this->customerService      =   $customerService;
    }

    public function createCustomer()
    {
        return $this->view( 'pages.dashboard.customers.create', [
            'title'     =>  __( 'Customers' )
        ]);
    }

    /**
     * Shows the list of available customers under a CRUD
     * list
     * @param void
     * @return backend vue
     */
    public function listCustomers()
    {
        return $this->view( 'pages.dashboard.customers.list', [
            'title'     =>  __( 'Customers' )
        ]);
    }

    /**
     * get list of avialable customers
     * @return json response
     */
    public function get( Customer $customer )
    {
        return $this->customerService->get();
    }

    /**
     * delete a customer
     * @param int customer id
     * @return json
     */
    public function delete( $id )
    {
        return $this->customerService->delete( $id );
    }

    /**
     * create a customer using the provided
     * form request
     * @param Request
     * @return json
     * @todo implement security validation
     */
    public function post( Request $request )
    {
        $data   =   $request->only([
            'name', 'surname', 'description', 'gender', 'phone', 'email', 'pobox', 'group_id', 'address'
        ]);

        return $this->customerService->create( $data );
    }

    /**
     * edit a customer using provided
     * form request
     * @param Request form data
     * @return json
     * @todo implement a request for the validation
     */
    public function put( $customer_id, Request $request )
    {
        $data   =   $request->only([
            'name', 'surname', 'description', 'gender', 'phone', 'email', 'pobox', 'group_id', 'address'
        ]);

        return $this->customerService->update( $customer_id, $data );
    }

    /**
     * Get specific customers order
     * @param Customer entity
     * @return json
     */
    public function getOrders( $id )
    {
        return $this->customerService->get( $id )->orders;
    }

    /**
     * Get Model Schema
     * which describe the field expected on post/put
     * requests
     * @return json
     */
    public function schema()
    {
        return [
            'post'  =>  [
                'name'          =>  'string', 
                'surname'       =>  'string', 
                'description'   =>  'string', 
                'gender'        =>  'string[male|female]', 
                'phone'         =>  'string', 
                'email'         =>  'string[email]', 
                'pobox'         =>  'string', 
                'group_id'      =>  'number',
                'address'       =>  [
                    'billing'   =>  [
                        'name'          =>  'string',
                        'surname'       =>  'string',
                        'phone'         =>  'string',
                        'address_1'     =>  'string',
                        'address_2'     =>  'string',
                        'country'       =>  'string',
                        'city'          =>  'string',
                        'pobox'         =>  'string',
                        'company'       =>  'string',
                    ], 
                    'shipping'   =>  [
                        'name'          =>  'string',
                        'surname'       =>  'string',
                        'phone'         =>  'string',
                        'address_1'     =>  'string',
                        'address_2'     =>  'string',
                        'country'       =>  'string',
                        'city'          =>  'string',
                        'pobox'         =>  'string',
                        'company'       =>  'string',
                    ]
                ]
            ], 
            'put'   =>  [
                'name'          =>  'string', 
                'surname'       =>  'string', 
                'description'   =>  'string', 
                'gender'        =>  'string[male|female]', 
                'phone'         =>  'string', 
                'email'         =>  'string[email]', 
                'pobox'         =>  'string', 
                'group_id'      =>  'number'
            ]
        ];
    }

    public function editCustomer( Customer $customer )
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'title'         =>  sprintf( __( 'Edit Customer : %s' ), $customer->name ),
            'description'   =>  __( 'Edit an existing customer.' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.customers/' . $customer->id ),
            'returnLink'    =>  url( '/dashboard/customers' ),
            'submitMethod'  =>  'PUT',
            'mainFieldLabel'    =>  __( 'Customer Name' ),
            'saveButton'    =>  __( 'Update Customer' ),
            'srcUrl'        =>  url( '/api/nexopos/v4/crud/ns.customers/form-config/' . $customer->id ),
            'customer'      =>  $customer
        ]);
    }

    /**
     * get the address informations saved
     * under a specific customer id
     * @param int customer id
     * @return array
     */
    public function getAddresses( $id )
    {
        return $this->customerService->getCustomerAddresses( $id );
    }

    public function deleteUsingEmail( $email )
    {
        return $this->customerService->deleteUsingEmail( $email );
    }
}

