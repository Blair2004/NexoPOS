<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\Procurement;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\ProviderService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProviderProcurementsCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.providers-procurements';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_procurements';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = '/providers/procurements';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.providers-procurements';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = Procurement::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => true,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_procurements.author' ],
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
     * @var ProviderService
     */
    protected $providerService;

    /**
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->providerService = app()->make( ProviderService::class );
    }

    public function hook( $query ): void
    {
        $query->where( 'provider_id', request()->query( 'provider_id' ) );
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
            'list_title' => __( 'Provider Procurements List' ),
            'list_description' => __( 'Display all provider procurements.' ),
            'no_entry' => __( 'No provider procurements has been registered' ),
            'create_new' => __( 'Add a new provider procurement' ),
            'create_title' => __( 'Create a new provider procurement' ),
            'create_description' => __( 'Register a new provider procurement and save it.' ),
            'edit_title' => __( 'Edit provider procurement' ),
            'edit_description' => __( 'Modify  Provider Procurement.' ),
            'back_to_list' => __( 'Return to Provider Procurements' ),
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
                            'name' => 'name',
                            'label' => __( 'Name' ),
                            'value' => $entry->name ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'delivery_status',
                            'label' => __( 'Delivery Status' ),
                            'value' => $entry->delivery_status ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'delivery_time',
                            'label' => __( 'Delivered On' ),
                            'value' => $entry->delivery_time ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'invoice_reference',
                            'label' => __( 'Invoice' ),
                            'value' => $entry->invoice_reference ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'payment_status',
                            'label' => __( 'Payment Status' ),
                            'value' => $entry->payment_status ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'tax_value',
                            'label' => __( 'Tax' ),
                            'value' => $entry->tax_value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'total_items',
                            'label' => __( 'Total Items' ),
                            'value' => $entry->total_items ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'value',
                            'label' => __( 'Value' ),
                            'value' => $entry->value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'created_at',
                            'label' => __( 'Created_at' ),
                            'value' => $entry->created_at ?? '',
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
    public function filterPutInputs( $inputs, Procurement $entry )
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
    public function afterPost( $request, Procurement $entry )
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
        if ( $namespace == 'ns.providers-procurements' ) {
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
                'width' => '200px',
                '$sort' => false,
            ],
            'delivery_status' => [
                'label' => __( 'Delivery' ),
                '$direction' => '',
                'width' => '120px',
                '$sort' => false,
            ],
            'payment_status' => [
                'label' => __( 'Payment' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'tax_value' => [
                'label' => __( 'Tax' ),
                '$direction' => '',
                'width' => '100px',
                '$sort' => false,
            ],
            'total_items' => [
                'label' => __( 'Items' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'value' => [
                'label' => __( 'Value' ),
                '$direction' => '',
                'width' => '150px',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __( 'By' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __( 'Created At' ),
                'width' => '200px',
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
        $entry->tax_value = (string) ns()->currency->define( $entry->tax_value );
        $entry->value = (string) ns()->currency->define( $entry->value );

        $entry->delivery_status = $this->providerService->getDeliveryStatusLabel( $entry->delivery_status );
        $entry->payment_status = $this->providerService->getPaymentStatusLabel( $entry->payment_status );

        // you can make changes here
        $entry->action(
            identifier: 'delete', // Prioritize 'identifier'
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.procurements/' . $entry->id ),
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
                if ( $entity instanceof Procurement ) {
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
            'list' => ns()->url( 'dashboard/' . '/providers/procurements' ),
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
