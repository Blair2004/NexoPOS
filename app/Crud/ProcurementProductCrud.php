<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\ProcurementProduct;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProcurementProductCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.procurements-products';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_procurements_products';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'procurements/products';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.procurements-products';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = ProcurementProduct::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => false,
        'read' => true,
        'update' => true,
        'delete' => false, // cannot be deleted
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_procurements as procurement', 'procurement.id', '=', 'nexopos_procurements_products.procurement_id' ],
        [ 'nexopos_units as unit', 'unit.id', '=', 'nexopos_procurements_products.unit_id' ],
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_procurements_products.author' ],
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
        'procurement' => [ 'name' ],
        'unit' => [ 'name' ],
        'user' => [ 'username' ],
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
            'list_title' => __( 'Procurement Products List' ),
            'list_description' => __( 'Display all procurement products.' ),
            'no_entry' => __( 'No procurement products has been registered' ),
            'create_new' => __( 'Add a new procurement product' ),
            'create_title' => __( 'Create a new procurement product' ),
            'create_description' => __( 'Register a new procurement product and save it.' ),
            'edit_title' => __( 'Edit procurement product' ),
            'edit_description' => __( 'Modify  Procurement Product.' ),
            'back_to_list' => __( 'Return to Procurement Products' ),
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
                'name' => 'name',
                'value' => $entry->name ?? '',
                'description' => __( 'Provide a name to the resource.' ),
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'datetimepicker',
                            'name' => 'expiration_date',
                            'label' => __( 'Expiration Date' ),
                            'value' => $entry->expiration_date ?? '',
                            'description' => __( 'Define what is the expiration date of the product.' ),
                        ],
                    ],
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
    public function filterPutInputs( $inputs, ProcurementProduct $entry )
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
    public function afterPost( $request, ProcurementProduct $entry )
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
        if ( $namespace == 'ns.procurements-products' ) {
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
            'name' => [
                'label' => __( 'Name' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'unit_name' => [
                'label' => __( 'Unit' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'procurement_name' => [
                'label' => __( 'Procurement' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'quantity' => [
                'label' => __( 'Quantity' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'total_purchase_price' => [
                'label' => __( 'Total Price' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'barcode' => [
                'label' => __( 'Barcode' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'expiration_date' => [
                'label' => __( 'Expiration Date' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __( 'Author' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'On' ),
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
        foreach ( [ 'gross_purchase_price', 'net_purchase_price', 'total_purchase_price', 'purchase_price' ] as $label ) {
            $entry->$label = (string) ns()->currency->define( $entry->$label );
        }

        $entry->action(
            label: __( 'Edit' ),
            identifier: 'edit',
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id ),
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
                if ( $entity instanceof ProcurementProduct ) {
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
            'list' => ns()->url( 'dashboard/' . 'procurements/products' ),
            'create' => 'javascript:void(0)', //ns()->url( 'dashboard/' . '/procurements/products/create' ),
            'edit' => ns()->url( 'dashboard/' . 'procurements/products/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.procurements-products' ),
            'put' => ns()->url( 'api/crud/' . 'ns.procurements-products/{id}' . '' ),
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
            // ...
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
