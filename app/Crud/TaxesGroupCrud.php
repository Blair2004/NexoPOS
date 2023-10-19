<?php

namespace App\Crud;

use App\Models\TaxGroup;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Users;
use Exception;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class TaxesGroupCrud extends CrudService
{
    /**
     * define the base table
     */
    protected $table = 'nexopos_taxes_groups';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.taxes-groups';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.taxes-groups';

    /**
     * Model Used
     */
    protected $model = TaxGroup::class;

    /**
     * Adding relation
     */
    public $relations = [
        [ 'nexopos_users as user', 'nexopos_taxes_groups.author', '=', 'user.id' ],
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
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter( $this->namespace . '-crud-actions', [ $this, 'setActions' ], 10, 2 );
    }

    protected $permissions = [
        'create' => 'nexopos.create.taxes',
        'read' => 'nexopos.read.taxes',
        'update' => 'nexopos.update.taxes',
        'delete' => 'nexopos.delete.taxes',
    ];

    /**
     * Return the label used for the crud
     * instance
     *
     * @return  array
     **/
    public function getLabels()
    {
        return [
            'list_title' => __( 'Taxes Groups List' ),
            'list_description' => __( 'Display all taxes groups.' ),
            'no_entry' => __( 'No taxes groups has been registered' ),
            'create_new' => __( 'Add a new tax group' ),
            'create_title' => __( 'Create a new tax group' ),
            'create_description' => __( 'Register a new tax group and save it.' ),
            'edit_title' => __( 'Edit tax group' ),
            'edit_description' => __( 'Modify  Tax Group.' ),
            'back_to_list' => __( 'Return to Taxes Groups' ),
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
     * @return  array of field
     */
    public function getForm( $entry = null )
    {
        return [
            'main' => [
                'label' => __( 'Name' ),
                'name' => 'name',
                'value' => $entry->name ?? '',
                'validation' => 'required',
                'description' => __( 'Provide a name to the resource.' ),
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'name' => 'description',
                            'type' => 'textarea',
                            'value' => $entry->description ?? '',
                            'label' => __( 'Description' ),
                            'description' => __( 'Provide a short description to the tax group.' ),
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
    public function filterPutInputs( $inputs, TaxGroup $entry )
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
     * @return  void
     */
    public function afterPost( $request, TaxGroup $entry )
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
        if ( $namespace == 'ns.taxes-groups' ) {
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
    public function setActions( CrudEntry $entry, $namespace )
    {
        // you can make changes here
        $entry->addAction( 'edit', [
            'label' => __( 'Edit' ),
            'namespace' => 'edit',
            'type' => 'GOTO',
            'index' => 'id',
            'url' => ns()->url( '/dashboard/' . 'taxes/groups' . '/edit/' . $entry->id ),
        ]);

        $entry->addAction( 'delete', [
            'label' => __( 'Delete' ),
            'namespace' => 'delete',
            'type' => 'DELETE',
            'url' => ns()->url( '/api/nexopos/v4/crud/ns.taxes-groups/' . $entry->id ),
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
                if ( $entity instanceof TaxGroup ) {
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
        return [
            'list' => ns()->url( 'dashboard/' . 'taxes/groups' ),
            'create' => ns()->url( 'dashboard/' . 'taxes/groups/create' ),
            'edit' => ns()->url( 'dashboard/' . 'taxes/groups/edit/{id}' ),
            'post' => ns()->url( 'api/nexopos/v4/crud/' . 'ns.taxes-groups' ),
            'put' => ns()->url( 'api/nexopos/v4/crud/' . 'ns.taxes-groups/' . '{id}' ),
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
