<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\ProcurementProduct;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProviderProductsCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.providers-products';

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
    protected $slug = '/dashboard/providers';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.providers-products';

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
        'update' => false,
        'delete' => false,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_taxes_groups as tax_group', 'nexopos_procurements_products.tax_group_id', '=', 'tax_group.id' ],
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
        'tax_group' => [ 'name' ],
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
            'list_title' => __( 'Provider Products List' ),
            'list_description' => __( 'Display all Provider Products.' ),
            'no_entry' => __( 'No Provider Products has been registered' ),
            'create_new' => __( 'Add a new Provider Product' ),
            'create_title' => __( 'Create a new Provider Product' ),
            'create_description' => __( 'Register a new Provider Product and save it.' ),
            'edit_title' => __( 'Edit Provider Product' ),
            'edit_description' => __( 'Modify Provider Product.' ),
            'back_to_list' => __( 'Return to Provider Products' ),
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
                        // ...
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
        if ( $namespace == 'ns.providers-products' ) {
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
            'purchase_price' => [
                'label' => __( 'Purchase Price' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'quantity' => [
                'label' => __( 'Quantity' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'tax_group_name' => [
                'label' => __( 'Tax Group' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'barcode' => [
                'label' => __( 'Barcode' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'expiration_date' => [
                'label' => __( 'Expiration Date' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'tax_type' => [
                'label' => __( 'Tax Type' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'tax_value' => [
                'label' => __( 'Tax Value' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'total_purchase_price' => [
                'label' => __( 'Total Price' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->purchase_price = ns()->currency->define( $entry->purchase_price )->format();
        $entry->tax_value = ns()->currency->define( $entry->tax_value )->format();
        $entry->total_purchase_price = ns()->currency->define( $entry->total_purchase_price )->format();
        $entry->expiration_date = $entry->expiration_date ?: __( 'N/A' );

        // you can make changes here
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id )
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.providers-products/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ]
        );

        return $entry;
    }

    public function hook( $query ): void
    {
        $query->whereIn( 'procurement_id', explode( ',', request()->query( 'procurements' ) ) );
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
            'list' => ns()->url( 'dashboard/' . '/dashboard/providers' ),
            'create' => false,
            'edit' => false,
            'post' => false,
            'put' => false,
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
