<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\ProductUnitQuantity;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProductUnitQuantitiesCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.products-units';

    /**
     * define the base table
     */
    protected $table = 'nexopos_products_unit_quantities';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.products-units';

    /**
     * Model Used
     */
    protected $model = ProductUnitQuantity::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => false,
        'read' => 'nexopos.read.products',
        'update' => false,
        'delete' => false,
    ];

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_products as products', 'products.id', '=', 'nexopos_products_unit_quantities.product_id' ],
        [ 'nexopos_units as units', 'units.id', '=', 'nexopos_products_unit_quantities.unit_id' ],
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
    public $fillable = [];

    /**
     * showing the options here is pointless.
     */
    protected $showOptions = false;

    /**
     * Bulk options are uselss here.
     */
    protected $showCheckboxes = false;

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
            'list_title' => __( 'Product Unit Quantities List' ),
            'list_description' => __( 'Display all product unit quantities.' ),
            'no_entry' => __( 'No product unit quantities has been registered' ),
            'create_new' => __( 'Add a new product unit quantity' ),
            'create_title' => __( 'Create a new product unit quantity' ),
            'create_description' => __( 'Register a new product unit quantity and save it.' ),
            'edit_title' => __( 'Edit product unit quantity' ),
            'edit_description' => __( 'Modify  Product Unit Quantity.' ),
            'back_to_list' => __( 'Return to Product Unit Quantities' ),
        ];
    }

    public function hook( $query ): void
    {
        if ( request()->query( 'product_id' ) ) {
            $query->where( 'product_id', request()->query( 'product_id' ) );
        }
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
                            'name' => 'created_at',
                            'label' => __( 'Created_at' ),
                            'value' => $entry->created_at ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'id',
                            'label' => __( 'Id' ),
                            'value' => $entry->id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'product_id',
                            'label' => __( 'Product id' ),
                            'value' => $entry->product_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'quantity',
                            'label' => __( 'Quantity' ),
                            'value' => $entry->quantity ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'type',
                            'label' => __( 'Type' ),
                            'value' => $entry->type ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'unit_id',
                            'label' => __( 'Unit Id' ),
                            'value' => $entry->unit_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'updated_at',
                            'label' => __( 'Updated_at' ),
                            'value' => $entry->updated_at ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'uuid',
                            'label' => __( 'Uuid' ),
                            'value' => $entry->uuid ?? '',
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
    public function filterPutInputs( $inputs, ProductUnitQuantity $entry )
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
    public function afterPost( $request, ProductUnitQuantity $entry )
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
        if ( $namespace == 'ns.products-units' ) {
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
            'products_name' => [
                'label' => __( 'Product' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'units_name' => [
                'label' => __( 'Unit' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'quantity' => [
                'label' => __( 'Quantity' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'updated_at' => [
                'label' => __( 'Updated At' ),
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
                if ( $entity instanceof ProductUnitQuantity ) {
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
            'list' => 'javascript:void(0)', // ns()->url( 'dashboard/' . 'products/units' ),
            'create' => 'javascript:void(0)', // ns()->url( 'dashboard/' . 'products/units/create' ),
            'edit' => 'javascript:void(0)', // ns()->url( 'dashboard/' . 'products/units/edit/' ),
            'post' => 'javascript:void(0)', // ns()->url( 'dashboard/' . 'products/units' ),
            'put' => 'javascript:void(0)', // ns()->url( 'dashboard/' . 'products/units/' . '' ),
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
