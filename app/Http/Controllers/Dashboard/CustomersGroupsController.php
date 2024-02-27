<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\CustomerGroupCrud;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use App\Models\Customer;
use App\Models\CustomerGroup;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomersGroupsController extends DashboardController
{
    public function listCustomersGroups()
    {
        return CustomerGroupCrud::table();
    }

    public function createCustomerGroup()
    {
        return CustomerGroupCrud::form();
    }

    public function editCustomerGroup( CustomerGroup $group )
    {
        return CustomerGroupCrud::form( $group );
    }

    /**
     * get a list or a single customer
     * group using a provided id
     *
     * @param int customer id
     * @return Model
     */
    public function get( $id = null )
    {
        if ( $id !== null ) {
            return CustomerGroup::find( $id );
        }

        return CustomerGroup::get();
    }

    /**
     * delete a single customer group
     *
     * @param int customer group id
     */
    public function delete( $id )
    {
        $group = CustomerGroup::find( $id );
        if ( $group instanceof CustomerGroup ) {
            if ( $group->customers->count() > 0 ) {
                throw new Exception( __( 'Unable to delete a group to which customers are still assigned.' ) );
            }

            /**
             * @todo dispatch action
             * while deleting a customer group
             */
            $group->delete();

            return [
                'status' => 'success',
                'message' => __( 'The customer group has been deleted.' ),
            ];
        }

        throw new Exception( __( 'Unable to find the requested group.' ) );
    }

    /**
     * @todo implement validation
     *
     * @param object Request
     * @return array
     */
    public function post( Request $request )
    {
        $fields = $request->only( [
            'name',
            'description',
            'reward_system_id',
        ] );

        $group = new CustomerGroup;
        foreach ( $fields as $name => $value ) {
            $group->$name = $value;
        }
        $group->author = Auth::id();
        $group->save();

        return [
            'status' => 'success',
            'message' => __( 'The customer group has been successfully created.' ),
            'data' => compact( 'group' ),
        ];
    }

    /**
     * edit a customer group
     *
     * @param object Request
     * @param int customer group id
     * @return array
     */
    public function put( Request $request, $id )
    {
        $group = CustomerGroup::find( $id );
        $fields = $request->only( [
            'name',
            'description',
            'reward_system_id',
        ] );

        if ( $group instanceof CustomerGroup ) {
            foreach ( $fields as $name => $value ) {
                $group->$name = $value;
            }
        }

        $group->author = Auth::id();
        $group->save();

        return [
            'status' => 'success',
            'message' => __( 'The customer group has been successfully saved.' ),
            'data' => compact( 'group' ),
        ];
    }

    public function transferOwnership( Request $request )
    {
        $from = $request->input( 'from' );
        $to = $request->input( 'to' );

        if ( $to === $from ) {
            throw new NotAllowedException( __( 'Unable to transfer customers to the same account.' ) );
        }

        $fromModel = CustomerGroup::findOrFail( $from );
        $toModel = CustomerGroup::findOrFail( $to );
        $customersID = $request->input( 'ids' );

        /**
         * if we attemps to move
         * all the customer from
         * one group to another
         */
        if ( $customersID === '*' ) {
            $customers = Customer::where( 'group_id', $fromModel->id )
                ->get();
            $customers
                ->forEach( function ( $customer ) use ( $toModel ) {
                    $customer->group_id = $toModel->id;
                    $customer->save();
                } );

            return [
                'status' => 'success',
                'message' => sprintf( __( 'All the customers has been transferred to the new group %s.' ), $toModel->name ),
            ];
        } elseif ( is_array( $customersID ) ) {
            /**
             * here we're trying to move
             * more than one customer using a
             * provided id
             */
            foreach ( $customersID as $customerID ) {
                $customer = Customer::where( 'id', $customerID )
                    ->where( 'group_id', $fromModel->id )
                    ->get()
                    ->forEach( function ( $customer ) use ( $toModel ) {
                        $customer->group_id = $toModel->id;
                        $customer->save();
                    } );
            }

            return [
                'status' => 'success',
                'message' => sprintf( __( 'The categories has been transferred to the group %s.' ), $toModel->name ),
            ];
        }

        throw new Exception( __( 'No customer identifier has been provided to proceed to the transfer.' ) );
    }

    public function getCustomers( $group_id )
    {
        $customerGroup = CustomerGroup::find( $group_id );

        if ( ! $customerGroup instanceof CustomerGroup ) {
            throw new NotFoundException( __( 'Unable to find the requested group using the provided id.' ) );
        }

        return $customerGroup->customers;
    }
}
