<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class CustomerOrderCrud extends OrderCrud
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.customers-orders';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.customers-orders';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = Order::class;

    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.read.orders',
        'update' => false,
        'delete' => 'nexopos.delete.orders',
    ];

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Customer Orders List' ),
            'list_description' => __( 'Display all customer orders.' ),
            'no_entry' => __( 'No customer orders has been registered' ),
            'create_new' => __( 'Add a new customer order' ),
            'create_title' => __( 'Create a new customer order' ),
            'create_description' => __( 'Register a new customer order and save it.' ),
            'edit_title' => __( 'Edit customer order' ),
            'edit_description' => __( 'Modify  Customer Order.' ),
            'back_to_list' => __( 'Return to Customer Orders' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     *
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    public function hook( $query ): void
    {
        if ( empty( request()->query( 'direction' ) ) ) {
            $query->orderBy( 'id', 'desc' );
        }

        if ( ! empty( request()->query( 'customer_id' ) ) ) {
            $query->where( 'customer_id', request()->query( 'customer_id' ) );
        }
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, Order $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function beforePost( $request )
    {
        if ( $this->permissions[ 'create' ] !== false ) {
            ns()->restrict( $this->permissions[ 'create' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function afterPost( $request, Order $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param  string
     * @return mixed
     */
    public function get( $param )
    {
        switch ( $param ) {
            case 'model': return $this->model;
                break;
        }
    }

    /**
     * Before updating a record
     *
     * @param Request $request
     * @param  object entry
     * @return void
     */
    public function beforePut( $request, $entry )
    {
        if ( $this->permissions[ 'update' ] !== false ) {
            ns()->restrict( $this->permissions[ 'update' ] );
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After updating a record
     *
     * @param Request $request
     * @param  object entry
     * @return void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.customers.orders' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
             **/
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }
        }
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return  false/array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        if ( $request->input( 'action' ) == 'delete_selected' ) {
            /**
             * Will control if the user has the permissoin to do that.
             */
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }

            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof Order ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'error' ]++;
                }
            }

            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     *
     * @return array of links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url( 'dashboard/' . 'dashboard/customers/orders' ),
            'create' => ns()->url( 'dashboard/' . 'dashboard/customers/orders/create' ),
            'edit' => ns()->url( 'dashboard/' . 'dashboard/customers/orders/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.customers.orders' ),
            'put' => ns()->url( 'api/crud/' . 'ns.customers.orders/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label' => __( 'Delete Selected Groups' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ] ),
            ],
        ] );
    }

    /**
     * get exports
     *
     * @return array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
