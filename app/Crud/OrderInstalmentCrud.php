<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\OrderInstalment;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class OrderInstalmentCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.orders-instalments';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_orders_instalments';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'orders/instalments';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.orders-instalments';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = OrderInstalment::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => false,
        'read' => true,
        'update' => true,
        'delete' => false,
    ];

    /**
     * Installment can't be managed from
     * the crud component (for now).
     */
    protected $showOptions = false;

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_orders as order', 'order.id', '=', 'nexopos_orders_instalments.order_id' ],
        [ 'nexopos_users as customer', 'customer.id', '=', 'order.customer_id' ],
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations = [
        // 'tab_name'      =>      [ YourRelatedModel::class, 'localkey_on_relatedmodel', 'foreignkey_on_crud_model' ],
    ];

    /**
     * Pick
     * Restrict columns you retrieve from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick = [
        'customer' => [ 'name' ],
    ];

    /**
     * Define where statement
     *
     * @var array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var array
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

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
            'list_title' => __( 'Order Instalments List' ),
            'list_description' => __( 'Display all Order Instalments.' ),
            'no_entry' => __( 'No Order Instalment has been registered' ),
            'create_new' => __( 'Add a new Order Instalment' ),
            'create_title' => __( 'Create a new Order Instalment' ),
            'create_description' => __( 'Register a new Order Instalment and save it.' ),
            'edit_title' => __( 'Edit Order Instalment' ),
            'edit_description' => __( 'Modify  Order Instalment.' ),
            'back_to_list' => __( 'Return to Order Instalment' ),
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

    /**
     * Fields
     *
     * @param  object/null
     * @return array of field
     */
    public function getForm( $entry = null )
    {
        return [
            'main' => [
                'label' => __( 'Name' ),
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description' => __( 'Provide a name to the resource.' ),
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'amount',
                            'label' => __( 'Amount' ),
                            'value' => $entry->amount ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'date',
                            'label' => __( 'Date' ),
                            'value' => $entry->date ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'id',
                            'label' => __( 'Id' ),
                            'value' => $entry->id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'order_id',
                            'label' => __( 'Order Id' ),
                            'value' => $entry->order_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'paid',
                            'label' => __( 'Paid' ),
                            'value' => $entry->paid ?? '',
                        ],                     ],
                ],
            ],
        ];
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
    public function filterPutInputs( $inputs, OrderInstalment $entry )
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
    public function afterPost( $request, OrderInstalment $entry )
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
        if ( $namespace == 'ns.orders-instalments' ) {
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
     * Define Columns
     */
    public function getColumns(): array
    {
        return [
            'customer_name' => [
                'label' => __( 'Customer' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'order_code' => [
                'label' => __( 'Order' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'amount' => [
                'label' => __( 'Amount' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'date' => [
                'label' => __( 'Date' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'paid' => [
                'label' => __( 'Paid' ),
                '$direction' => '',
                '$sort' => false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->amount = (string) ns()->currency->define( $entry->amount );
        $entry->{ '$cssClass' } = $entry->paid == 0 ? 'error' : 'success';
        $entry->paid = (bool) $entry->paid ? __( 'Yes' ) : __( 'No' );
        $entry->date = ns()->date->getFormatted( $entry->date );
        // you can make changes here
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id ),
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.orders-instalments/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ]
        );

        return $entry;
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
                if ( $entity instanceof OrderInstalment ) {
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
            'list' => ns()->url( 'dashboard/' . 'orders/instalments' ),
            'create' => false,
            'edit' => ns()->url( 'dashboard/' . 'orders/instalments/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.orders-instalments' ),
            'put' => ns()->url( 'api/crud/' . 'ns.orders-instalments/{id}' . '' ),
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
