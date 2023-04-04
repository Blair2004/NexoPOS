<?php

namespace App\Crud;

use App\Models\Provider;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Users;
use Exception;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class ProviderCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table = 'nexopos_providers';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.providers';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.providers';

    /**
     * Model Used
     */
    protected $model = Provider::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users', 'nexopos_users.id', '=', 'nexopos_providers.author' ],
    ];

    /**
     * Define where statement
     *
     * @var  array
     **/
    protected $listWhere = [];

    /**
     * Define where in statement
     *
     * @var  array
     */
    protected $whereIn = [];

    /**
     * Fields which will be filled during post/put
     */
    public $fillable = [];

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => 'nexopos.create.providers',
        'read' => 'nexopos.read.providers',
        'update' => 'nexopos.update.providers',
        'delete' => 'nexopos.delete.providers',
    ];

    /**
     * Define Constructor
     *
     * @param
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );
    }

    /**
     * Return the label used for the crud
     * instance
     *
     * @return  array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Providers List' ),
            'list_description' => __( 'Display all providers.' ),
            'no_entry' => __( 'No providers has been registered' ),
            'create_new' => __( 'Add a new provider' ),
            'create_title' => __( 'Create a new provider' ),
            'create_description' => __( 'Register a new provider and save it.' ),
            'edit_title' => __( 'Edit provider' ),
            'edit_description' => __( 'Modify  Provider.' ),
            'back_to_list' => __( 'Return to Providers' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     *
     * @return  bool
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Fields
     *
     * @param  object/null
     * @return  array of field
     */
    public function getForm( $entry = null )
    {
        return [
            'main' => [
                'label' => __( 'Name' ),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'description' => __( 'Provide a name to the resource.' ),
                'validation' => 'required',
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'email',
                            'label' => __( 'Email' ),
                            'description' => __( 'Provide the provider email. Might be used to send automated email.' ),
                            'value' => $entry->email ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'surname',
                            'label' => __( 'Surname' ),
                            'description' => __( 'Provider surname if necessary.' ),
                            'value' => $entry->surname ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'phone',
                            'label' => __( 'Phone' ),
                            'description' => __( 'Contact phone number for the provider. Might be used to send automated SMS notifications.' ),
                            'value' => $entry->phone ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'address_1',
                            'label' => __( 'Address 1' ),
                            'description' => __( 'First address of the provider.' ),
                            'value' => $entry->address_1 ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'address_2',
                            'label' => __( 'Address 2' ),
                            'description' => __( 'Second address of the provider.' ),
                            'value' => $entry->address_2 ?? '',
                        ], [
                            'type' => 'textarea',
                            'name' => 'description',
                            'label' => __( 'Description' ),
                            'description' => __( 'Further details about the provider' ),
                            'value' => $entry->description ?? '',
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
     * @return  array of fields
     */
    public function filterPostInputs( $inputs )
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return  array of fields
     */
    public function filterPutInputs( $inputs, Provider $entry )
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
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
     *
     * @param  Request $request
     * @param  Provider $entry
     * @return  void
     */
    public function afterPost( $request, Provider $entry )
    {
        return $request;
    }

    /**
     * get
     *
     * @param  string
     * @return  mixed
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
     *
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
     *
     * @param  array { namespace, id, type }
     * @return  array | throw Exception
     **/
    public function canAccess( $fields )
    {
        $users = app()->make( Users::class );

        if ( $users->is([ 'admin' ]) ) {
            return [
                'status' => 'success',
                'message' => __( 'The access is granted.' ),
            ];
        }

        throw new Exception( __( 'You don\'t have access to that ressource' ) );
    }

    /**
     * Before Delete
     *
     * @return  void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        if ( $namespace == 'ns.providers' ) {
            $this->allowedTo( 'delete' );
        }
    }

    /**
     * Define Columns
     *
     * @return  array of columns configuration
     */
    public function getColumns()
    {
        return [
            'name' => [
                'label' => __( 'Name' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'email' => [
                'label' => __( 'Email' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'phone' => [
                'label' => __( 'Phone' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'amount_due' => [
                'label' => __( 'Amount Due' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'amount_paid' => [
                'label' => __( 'Amount Paid' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'nexopos_users_username' => [
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
    public function setActions( CrudEntry $entry, $namespace )
    {
        $entry->phone = $entry->phone ?? __( 'N/A' );
        $entry->email = $entry->email ?? __( 'N/A' );

        $entry->amount_due = ns()->currency->define( $entry->amount_due )->format();
        $entry->amount_paid = ns()->currency->define( $entry->amount_paid )->format();

        $entry->addAction( 'edit', [
            'label' => __( 'Edit' ),
            'namespace' => 'edit',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'providers' . '/edit/' . $entry->id ),
        ]);

        $entry->addAction( 'see-procurements', [
            'label' => __( 'See Procurements' ),
            'namespace' => 'see-procurements',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'providers/' . $entry->id . '/procurements/' ),
        ]);

        $entry->addAction( 'see-products', [
            'label' => __( 'See Products' ),
            'namespace' => 'see-products',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'providers/' . $entry->id . '/products/' ),
        ]);

        $entry->addAction( 'delete', [
            'label' => __( 'Delete' ),
            'namespace' => 'delete',
            'type' => 'DELETE',
            'url' => ns()->url( '/api/nexopos/v4/crud/ns.providers/' . $entry->id ),
            'confirm' => [
                'message' => __( 'Would you like to delete this ?' ),
            ],
        ]);

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param    object Request with object
     * @return    false/array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user = app()->make( Users::class );
        if ( ! $user->is([ 'admin', 'supervisor' ]) ) {
            return response()->json([
                'status' => 'failed',
                'message' => __( 'You\'re not allowed to do this operation' ),
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status = [
                'success' => 0,
                'failed' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof Provider ) {
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
     *
     * @return  array of links
     */
    public function getLinks(): array
    {
        return  [
            'list' => ns()->url( 'dashboard/' . 'providers' ),
            'create' => ns()->url( 'dashboard/' . 'providers/create' ),
            'edit' => ns()->url( 'dashboard/' . 'providers/edit/' ),
            'post' => ns()->url( 'api/nexopos/v4/crud/' . 'ns.providers' ),
            'put' => ns()->url( 'api/nexopos/v4/crud/' . 'ns.providers/{id}' . '' ),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return  array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label' => __( 'Delete Selected Groups' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ]);
    }

    /**
     * get exports
     *
     * @return  array of export formats
     **/
    public function getExports()
    {
        return [];
    }
}
