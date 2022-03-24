<?php
namespace App\Crud;

use App\Exceptions\NotAllowedException;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\Users;
use TorMorten\Eventy\Facades\Events as Hook;
use Exception;
use App\Models\Procurement;
use App\Services\ProviderService;

class ProcurementCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table      =   'nexopos_procurements';

    /**
     * base route name
     */
    protected $identifier   =   '/procurements';

    /**
     * Define namespace
     * @param  string
     */
    protected $namespace  =   'ns.procurements';

    /**
     * Model Used
     */
    protected $model      =   Procurement::class;

    /**
     * Adding relation
     */
    public $relations   =  [
        [ 'nexopos_users as users', 'nexopos_procurements.author', '=', 'users.id' ],
        [ 'nexopos_providers as providers', 'nexopos_procurements.provider_id', '=', 'providers.id' ]
    ];

    /**
     * Define where statement
     * @var  array
    **/
    protected $listWhere    =   [];

    /**
     * Define where in statement
     * @var  array
     */
    protected $whereIn      =   [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable    =   [];

    /**
     * define permission
     */
    public $permissions     =   [
        'create'    =>  'nexopos.create.procurements',
        'read'      =>  'nexopos.read.procurements',
        'update'    =>  false,
        'delete'    =>  'nexopos.delete.procurements',
    ];

    /**
     * @var ProviderService
     */
    protected $providerService;

    /**
     * Define Constructor
     * @param  
     */
    public function __construct()
    {
        parent::__construct();

        $this->providerService  =   app()->make( ProviderService::class );

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud 
     * instance
     * @return  array
    **/
    public function getLabels()
    {
        return [
            'list_title'            =>  __( 'Procurements List' ),
            'list_description'      =>  __( 'Display all procurements.' ),
            'no_entry'              =>  __( 'No procurements has been registered' ),
            'create_new'            =>  __( 'Add a new procurement' ),
            'create_title'          =>  __( 'Create a new procurement' ),
            'create_description'    =>  __( 'Register a new procurement and save it.' ),
            'edit_title'            =>  __( 'Edit procurement' ),
            'edit_description'      =>  __( 'Modify  Procurement.' ),
            'back_to_list'          =>  __( 'Return to Procurements' ),
        ];
    }

    public function hook( $query )
    {
        /**
         * should not block default
         * crud sorting
         */
        if ( ! request()->query( 'active' ) && ! request()->query( 'direction' ) ) {
            $query->orderBy( 'created_at', 'desc' );
        }
    }

    /**
     * Check whether a feature is enabled
     * @return  boolean
    **/
    public function isEnabled( $feature )
    {
        return false; // by default
    }

    /**
     * Fields
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null ) 
    {
        return [
            'main' =>  [
                'label'         =>  __( 'Name' ),
                'name'          =>  'name',
                'value'         =>  $entry->name ?? '',
                'description'   =>  __( 'Provide a name to the resource.' )
            ],
            'tabs'  =>  [
                'general'   =>  [
                    'label'     =>  __( 'General' ),
                    'fields'    =>  [
                        [
                            'type'  =>  'text',
                            'name'  =>  'author',
                            'label' =>  __( 'Author' ),
                            'value' =>  $entry->author ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'created_at',
                            'label' =>  __( 'Created At' ),
                            'value' =>  $entry->created_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'description',
                            'label' =>  __( 'Description' ),
                            'value' =>  $entry->description ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'id',
                            'label' =>  __( 'Id' ),
                            'value' =>  $entry->id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'name',
                            'label' =>  __( 'Name' ),
                            'value' =>  $entry->name ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'provider_id',
                            'label' =>  __( 'Provider Id' ),
                            'value' =>  $entry->provider_id ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'status',
                            'label' =>  __( 'Status' ),
                            'value' =>  $entry->status ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'total_items',
                            'label' =>  __( 'Total Items' ),
                            'value' =>  $entry->total_items ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'updated_at',
                            'label' =>  __( 'Updated At' ),
                            'value' =>  $entry->updated_at ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'uuid',
                            'label' =>  __( 'Uuid' ),
                            'value' =>  $entry->uuid ?? '',
                        ], [
                            'type'  =>  'text',
                            'name'  =>  'value',
                            'label' =>  __( 'Value' ),
                            'value' =>  $entry->value ?? '',
                        ],                     ]
                ]
            ]
        ];
    }

    /**
     * Filter POST input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPostInputs( $inputs )
    {
        if ( empty( $inputs[ 'invoice_date' ] ) ) {
            $inputs[ 'invoice_date' ]   =   ns()->date->getNowFormatted();
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, Procurement $entry )
    {
        if ( empty( $inputs[ 'invoice_date' ] ) ) {
            $inputs[ 'invoice_date' ]   =   ns()->date->getNowFormatted();
        }

        return $inputs;
    }

    /**
     * Before saving a record
     * @param  Request $request
     * @return  void
     */
    public function beforePost( $request )
    {
        $this->allowedTo( 'create' );

        return $request;
    }

    /**
     * After saving a record
     * @param  Request $request
     * @param  Procurement $entry
     * @return  void
     */
    public function afterPost( $request, Procurement $entry )
    {
        return $request;
    }

    
    /**
     * get
     * @param  string
     * @return  mixed
     */
    public function get( $param )
    {
        switch( $param ) {
            case 'model' : return $this->model ; break;
        }
    }

    /**
     * Before updating a record
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function beforePut( $request, $entry )
    {
        $this->allowedTo( 'update' );

        return $request;
    }

    /**
     * After updating a record
     * @param  Request $request
     * @param  object entry
     * @return  void
     */
    public function afterPut( $request, $entry )
    {
        return $request;
    }
    
    /**
     * Protect an access to a specific crud UI
     * @param  array { namespace, id, type }
     * @return  array | throw Exception
    **/
    public function canAccess( $fields )
    {
        $users      =   app()->make( Users::class );
        
        if ( $users->is([ 'admin' ]) ) {
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The access is granted.' )
            ];
        }

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     * @return  void
     */
    public function beforeDelete( $namespace, $id, $model ) {
        if ( $namespace == 'ns.procurements' ) {
            $this->allowedTo( 'delete' );
        }
    }

    /**
     * Define Columns
     * @return  array of columns configuration
     */
    public function getColumns() {
        return [
            'name'  =>  [
                'label'  =>  __( 'Name' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'providers_name'  =>  [
                'label'  =>  __( 'Provider' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'delivery_status'  =>  [
                'label'  =>  __( 'Delivery Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'payment_status'  =>  [
                'label'  =>  __( 'Payment Status' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'invoice_date'  =>  [
                'label'  =>  __( 'Invoice Date' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'value'  =>  [
                'label'         =>  __( 'Sale Value' ),
                '$direction'    =>  '',
                'width'         =>  '150px',
                '$sort'         =>  false
            ],
            'cost'  =>  [
                'label'         =>  __( 'Purchase Value' ),
                '$direction'    =>  '',
                'width'         =>  '150px',
                '$sort'         =>  false
            ],
            'tax_value'  =>  [
                'label'         =>  __( 'Taxes' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
            'users_username'    =>  [
                'label'         =>  __( 'Author' ),
                '$direction'    =>  '',
                '$sort'         =>  false
            ],
        ];
    }

    /**
     * Define actions
     */
    public function setActions( $entry, $namespace )
    {
        // Don't overwrite
        $entry->{ '$checked' }  =   false;
        $entry->{ '$toggled' }  =   false;
        $entry->{ '$id' }       =   $entry->id;

        $entry->delivery_status     =   $this->providerService->getDeliveryStatusLabel( $entry->delivery_status );
        $entry->payment_status      =   $this->providerService->getPaymentStatusLabel( $entry->payment_status );

        $entry->value       =   ns()
            ->currency
            ->define( $entry->value )
            ->format();

        $entry->cost       =   ns()
            ->currency
            ->define( $entry->cost )
            ->format();

        $entry->tax_value   =   ns()
            ->currency
            ->define( $entry->tax_value )
            ->format();

        // you can make changes here
        $entry->{'$actions'}    =   [
            [
                'label'         =>      __( 'Edit' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'procurements' . '/edit/' . $entry->id )
            ], [
                'label'         =>      __( 'Invoice' ),
                'namespace'     =>      'edit',
                'type'          =>      'GOTO',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/dashboard/' . 'procurements' . '/edit/' . $entry->id . '/invoice' )
            ], [
                'label'         =>      __( 'Refresh' ),
                'namespace'     =>      'refresh',
                'type'          =>      'GET',
                'index'         =>      'id',
                'url'           =>     ns()->url( '/api/nexopos/v4/procurements/' . $entry->id . '/refresh' ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to refresh this ?' ),
                ]
            ], [
                'label'     =>  __( 'Delete' ),
                'namespace' =>  'delete',
                'type'      =>  'DELETE',
                'url'       => ns()->url( '/api/nexopos/v4/crud/ns.procurements/' . $entry->id ),
                'confirm'   =>  [
                    'message'  =>  __( 'Would you like to delete this ?' ),
                ]
            ]
        ];

        return $entry;
    }

    
    /**
     * Bulk Delete Action
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction( Request $request ) 
    {
        if ( $request->input( 'action' ) == 'delete_selected' ) {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }

            $status     =   [
                'success'   =>  0,
                'failed'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Procurement ) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }
            return $status;
        }

        return Hook::filter( $this->namespace . '-catch-action', false, $request );
    }

    /**
     * get Links
     * @return  array of links
     */
    public function getLinks()
    {
        return  [
            'list'      =>  'procurements',
            'create'    =>  'procurements/create',
            'edit'      =>  'procurements/edit'
        ];
    }

    /**
     * Get Bulk actions
     * @return  array of actions
    **/
    public function getBulkActions()
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label'         =>  __( 'Delete Selected Groups' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace
                ])
            ]
        ]);
    }

    /**
     * get exports
     * @return  array of export formats
    **/
    public function getExports()
    {
        return [];
    }
}