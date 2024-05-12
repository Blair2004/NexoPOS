<?php

namespace App\Crud;

use App\Casts\ProductHistoryActionCast;
use App\Exceptions\NotAllowedException;
use App\Models\ProductHistory;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class GlobalProductHistoryCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.global-products-history';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_products_histories';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = '/products/history';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.global-products-history';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = ProductHistory::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
    ];

    protected $showOptions = false;

    protected $showCheckboxes = false;

    public $casts = [
        'operation_type' => ProductHistoryActionCast::class,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_products_histories.author' ],
        [ 'nexopos_products as product', 'product.id', '=', 'nexopos_products_histories.product_id' ],
        [ 'nexopos_units as unit', 'unit.id', '=', 'nexopos_products_histories.unit_id' ],
        'leftJoin' => [
            [ 'nexopos_procurements as procurement', 'procurement.id', '=', 'nexopos_products_histories.procurement_id' ],
            [ 'nexopos_orders as order', 'order.id', '=', 'nexopos_products_histories.order_id' ],
        ],
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
    public $pick = [];

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

    /**
     * If few fields should only be filled
     * those should be listed here.
     */
    public $fillable = [];

    /**
     * If fields should be ignored during saving
     * those fields should be listed here
     */
    public $skippable = [];

    /**
     * Determine if the options column should display
     * before the crud columns
     */
    protected $prependOptions = false;

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
            'list_title' => __( 'Product Histories' ),
            'list_description' => __( 'Display all product stock flow.' ),
            'no_entry' => __( 'No products stock flow has been registered' ),
            'create_new' => __( 'Add a new products stock flow' ),
            'create_title' => __( 'Create a new products stock flow' ),
            'create_description' => __( 'Register a new products stock flow and save it.' ),
            'edit_title' => __( 'Edit products stock flow' ),
            'edit_description' => __( 'Modify  Globalproducthistorycrud.' ),
            'back_to_list' => __( 'Return to Product Histories' ),
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
            // ...
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
    public function filterPutInputs( $inputs, ProductHistory $entry )
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
    public function afterPost( $request, ProductHistory $entry )
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
        if ( $namespace == 'ns.global-products-history' ) {
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
            'product_name' => [
                'label' => __( 'Product' ),
                '$direction' => '',
                'width' => '300px',
                '$sort' => false,
            ],
            'procurement_name' => [
                'label' => __( 'Procurement' ),
                '$direction' => '',
                'width' => '200px',
                '$sort' => false,
            ],
            'order_code' => [
                'label' => __( 'Order' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'operation_type' => [
                'label' => __( 'Operation Type' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'unit_name' => [
                'label' => __( 'Unit' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'before_quantity' => [
                'label' => __( 'Initial Quantity' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'quantity' => [
                'label' => __( 'Quantity' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'after_quantity' => [
                'label' => __( 'New Quantity' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'total_price' => [
                'label' => __( 'Total Price' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __( 'Author' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'Created At' ),
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
        $entry->procurement_name = $entry->procurement_name ?: __( 'N/A' );
        $entry->order_code = $entry->order_code ?: __( 'N/A' );
        $entry->total_price = ns()->currency->fresh( $entry->total_price )->format();

        // you can make changes here
        $entry->action(
            label: __( 'Delete' ),
            identifier: 'delete',
            url: ns()->url( '/api/crud/ns.global-products-history/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ]
        );

        return $entry;
    }

    public function hook( $query ): void
    {
        $query->orderBy( 'id', 'desc' );
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
                if ( $entity instanceof ProductHistory ) {
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
            'list' => ns()->url( 'dashboard/' . '/products/history' ),
            'create' => ns()->url( 'dashboard/' . '/products/history/create' ),
            'edit' => ns()->url( 'dashboard/' . '/products/history/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.global-products-history' ),
            'put' => ns()->url( 'api/crud/' . 'ns.global-products-history/{id}' . '' ),
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
