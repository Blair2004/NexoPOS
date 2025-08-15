<?php

namespace App\Crud;

use App\Classes\CrudTable;
use App\Exceptions\NotAllowedException;
use App\Models\PermissionAccess;
use App\Services\CrudEntry;
use App\Services\CrudService;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class PermissionAccessCrud extends CrudService
{
    /**
     * Defines if the crud class should be automatically discovered.
     * If set to "true", no need register that class on the "CrudServiceProvider".
     */
    const AUTOLOAD = true;

    /**
     * define the base table
     *
     * @param string
     */
    protected $table = 'nexopos_permissions_access';

    /**
     * default slug
     *
     * @param string
     */
    protected $slug = 'permissions-access';

    /**
     * To be able to autoload the class, we need to define
     * the identifier on a constant.
     */
    const IDENTIFIER = 'ns.permissions-access';

    /**
     * Model Used
     *
     * @param string
     */
    protected $model = PermissionAccess::class;

    /**
     * Define permissions
     *
     * @param array
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
     * Other possible combinatsion includes "leftJoin", "rightJoin", "innerJoin"
     *
     * Left Join Example
     * public $relations = [
     *  'leftJoin' => [
     *      [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *  ]
     * ];
     *
     * @param array
     */
    public $relations = [
        'leftJoin' => [
            [ 'nexopos_users as requester', 'requester.id', '=', 'nexopos_permissions_access.requester_id' ],
            [ 'nexopos_users as granter', 'granter.id', '=', 'nexopos_permissions_access.granter_id' ],
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
     * Export Columns defines the columns that
     * should be included on the exported csv file.
     */
    protected $exportColumns = []; // @getColumns will be used by default.

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
        'requester' => ['username'],
        'granter' => ['username'],
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
     * Will make the options column available per row if
     * set to "true". Otherwise it will be hidden.
     */
    protected $showOptions = true;

    /**
     * In case this crud instance is used on a search-select field,
     * the following attributes are used to auto-populate the "options" attribute.
     */
    protected $optionAttribute = [
        'value' => 'id',
        'label' => 'name',
    ];

    /**
     * Return the label used for the crud object.
     **/
    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __( 'PermissionAccesses List' ),
            list_description: __( 'Display all permissionaccesses.' ),
            no_entry: __( 'No permissionaccesses has been registered' ),
            create_new: __( 'Add a new permissionaccess' ),
            create_title: __( 'Create a new permissionaccess' ),
            create_description: __( 'Register a new permissionaccess and save it.' ),
            edit_title: __( 'Edit permissionaccess' ),
            edit_description: __( 'Modify  Permissionaccess.' ),
            back_to_list: __( 'Return to PermissionAccesses' ),
        );
    }

    /**
     * Defines the forms used to create and update entries.
     *
     * @param PermissionAccess $entry
     */
    public function getForm( ?PermissionAccess $entry = null ): array
    {
        return [];
    }

    /**
     * Filter POST input fields
     *
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs ): array
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( array $inputs, PermissionAccess $entry )
    {
        return $inputs;
    }

    /**
     * Trigger actions that are executed before the
     * crud entry is created.
     */
    public function beforePost( array $request ): array
    {
        $this->allowedTo( 'create' );

        return $request;
    }

    /**
     * Trigger actions that will be executed
     * after the entry has been created.
     */
    public function afterPost( array $request, PermissionAccess $entry ): array
    {
        return $request;
    }

    /**
     * A shortcut and secure way to access
     * senstive value on a read only way.
     */
    public function get( string $param ): mixed
    {
        switch ( $param ) {
            case 'model':
                return $this->model;
                break;
            default:
                return null;
                break;
        }
    }

    /**
     * Trigger actions that are executed before
     * the crud entry is updated.
     */
    public function beforePut( array $request, PermissionAccess $entry ): array
    {
        $this->allowedTo( 'update' );

        return $request;
    }

    /**
     * This trigger actions that are executed after
     * the crud entry is successfully updated.
     */
    public function afterPut( array $request, PermissionAccess $entry ): array
    {
        return $request;
    }

    /**
     * This triggers actions that will be executed ebfore
     * the crud entry is deleted.
     */
    public function beforeDelete( $identifier, $id, $model ): void
    {
        if ( $identifier == self::IDENTIFIER ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' ),
             *  ], 403 );
             **/
            if ( $this->permissions['delete'] !== false ) {
                ns()->restrict( $this->permissions['delete'] );
            } else {
                throw new NotAllowedException;
            }
        }
    }

    /**
     * Define columns and how it is structured.
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                identifier: 'requester_username',
                label: __( 'Requester' ),
            ),
            CrudTable::column(
                identifier: 'granter_username',
                label: __( 'Granter' ),
            ),
            CrudTable::column(
                identifier: 'permission',
                label: __( 'Permission' ),
            ),
            CrudTable::column(
                identifier: 'url',
                label: __( 'Url' ),
            ),
            CrudTable::column(
                identifier: 'status',
                label: __( 'Status' ),
            ),
            CrudTable::column(
                identifier: 'expired_at',
                label: __( 'Expired_at' ),
            ),
            CrudTable::column(
                identifier: 'created_at',
                label: __( 'Requested At' ),
            ),
            CrudTable::column(
                identifier: 'updated_at',
                label: __( 'Updated_at' ),
            ),
        );
    }

    /**
     * Define row actions.
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        /**
         * Declaring entry actions
         */
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            url: ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id )
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/' . self::IDENTIFIER . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ]
        );

        return $entry;
    }

    /**
     * trigger actions that are executed
     * when a bulk actio is posted.
     */
    public function bulkAction( Request $request ): array
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        if ( $request->input( 'action' ) == 'delete_selected' ) {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ( $this->permissions['delete'] !== false ) {
                ns()->restrict( $this->permissions['delete'] );
            } else {
                throw new NotAllowedException;
            }

            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof PermissionAccess ) {
                    $entity->delete();
                    $status['success']++;
                } else {
                    $status['error']++;
                }
            }

            return $status;
        }

        return Hook::filter( self::IDENTIFIER . '-catch-action', false, $request );
    }

    /**
     * Defines links used on the CRUD object.
     */
    public function getLinks(): array
    {
        return CrudTable::links(
            list: ns()->url( 'dashboard/' . 'permissions-access' ),
            create: ns()->url( 'dashboard/' . 'permissions-access/create' ),
            edit: ns()->url( 'dashboard/' . 'permissions-access/edit/' ),
            post: ns()->url( 'api/crud/' . self::IDENTIFIER ),
            put: ns()->url( 'api/crud/' . self::IDENTIFIER . '/{id}' . '' ),
        );
    }

    /**
     * Defines the bulk actions.
     **/
    public function getBulkActions(): array
    {
        return Hook::filter( self::IDENTIFIER . '-bulk', [
            [
                'label' => __( 'Delete Selected Entries' ),
                'identifier' => 'delete_selected',
                'url' => ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' => self::IDENTIFIER,
                ] ),
            ],
        ] );
    }

    /**
     * Defines the export configuration.
     **/
    public function getExports(): array
    {
        return [];
    }
}
