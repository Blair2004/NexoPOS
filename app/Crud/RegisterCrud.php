<?php

namespace App\Crud;

use App\Exceptions\NotAllowedException;
use App\Models\Register;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class RegisterCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.cash-registers';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_registers';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'cash-registers';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.cash-registers';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = Register::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => 'nexopos.create.registers',
        'read' => 'nexopos.read.registers',
        'update' => 'nexopos.update.registers',
        'delete' => 'nexopos.delete.registers',
    ];

    /**
     * Adding relation
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_users as user', 'nexopos_registers.author', '=', 'user.id' ],
        'leftJoin' => [
            [ 'nexopos_users as cashier', 'nexopos_registers.used_by', '=', 'cashier.id' ],
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
    public $pick = [
        'user' => [ 'username' ],
        'cashier' => [ 'username' ],
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
            'list_title' => __( 'Registers List' ),
            'list_description' => __( 'Display all registers.' ),
            'no_entry' => __( 'No registers has been registered' ),
            'create_new' => __( 'Add a new register' ),
            'create_title' => __( 'Create a new register' ),
            'create_description' => __( 'Register a new register and save it.' ),
            'edit_title' => __( 'Edit register' ),
            'edit_description' => __( 'Modify  Register.' ),
            'back_to_list' => __( 'Return to Registers' ),
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
                'validation' => 'required',
            ],
            'tabs' => [
                'general' => [
                    'label' => __( 'General' ),
                    'fields' => [
                        [
                            'type' => 'select',
                            'name' => 'status',
                            'label' => __( 'Status' ),
                            'options' => Helper::kvToJsOptions( [
                                Register::STATUS_DISABLED => __( 'Disabled' ),
                                Register::STATUS_CLOSED => __( 'Closed' ),
                            ] ),
                            'description' => __( 'Define what is the status of the register.' ),
                            'value' => $entry->status ?? '',
                            'validation' => 'required',
                        ],
                        [
                            'type' => 'textarea',
                            'name' => 'description',
                            'label' => __( 'Description' ),
                            'value' => $entry->description ?? '',
                            'description' => __( 'Provide mode details about this cash register.' ),
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
    public function filterPutInputs( $inputs, Register $entry )
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
    public function afterPost( $request, Register $entry )
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
        if ( $namespace == self::IDENTIFIER ) {
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

            if ( $model->status === Register::STATUS_OPENED ) {
                throw new NotAllowedException( __( 'Unable to delete a register that is currently in use' ) );
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
            'status' => [
                'label' => __( 'Status' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'cashier_username' => [
                'label' => __( 'Used By' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'balance' => [
                'label' => __( 'Balance' ),
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
        $entry->cashier_username = $entry->cashier_username ?: __( 'N/A' );
        $entry->balance = (string) ns()->currency->define( $entry->balance );

        // you can make changes here
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . 'cash-registers' . '/edit/' . $entry->id )
        );

        $entry->action(
            identifier: 'register-history', // Prioritize 'identifier'
            label: __( 'Register History' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . 'cash-registers' . '/history/' . $entry->id )
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/' . self::IDENTIFIER . '/' . $entry->id ),
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
                if ( $entity instanceof Register ) {
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
            'list' => ns()->url( 'dashboard/' . 'cash-registers' ),
            'create' => ns()->url( 'dashboard/' . 'cash-registers/create' ),
            'edit' => ns()->url( 'dashboard/' . 'cash-registers/edit/{id}' ),
            'post' => ns()->url( 'api/crud/' . self::IDENTIFIER ),
            'put' => ns()->url( 'api/crud/' . self::IDENTIFIER . '/{id}' . '' ),
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
