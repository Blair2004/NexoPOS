<?php

namespace App\Crud;

use App\Classes\CrudForm;
use App\Classes\CrudTable;
use App\Classes\FormInput;
use App\Models\Unit;
use App\Models\UnitGroup;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use App\Services\UsersService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TorMorten\Eventy\Facades\Events as Hook;

class UnitCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.units';

    /**
     * define the base table
     */
    protected $table = 'nexopos_units';

    /**
     * base route name
     */
    protected $mainRoute = 'ns.units';

    /**
     * Define namespace
     *
     * @param string $namespace The namespace for the CRUD class
     */
    protected $namespace = 'ns.units';

    /**
     * Model Used
     */
    protected $model = Unit::class;

    /**
     * Adding relation
     */
    public $relations = [
        'join' => [
            [ User::class, 'user' ],
        ],
        [ 'nexopos_units_groups as group', 'nexopos_units.group_id', '=', 'group.id' ],
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

    protected $permissions = [
        'create' => 'nexopos.create.products-units',
        'read' => 'nexopos.read.products-units',
        'update' => 'nexopos.update.products-units',
        'delete' => 'nexopos.delete.products-units',
    ];

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
            'list_title' => __( 'Units List' ),
            'list_description' => __( 'Display all units.' ),
            'no_entry' => __( 'No units has been registered' ),
            'create_new' => __( 'Add a new unit' ),
            'create_title' => __( 'Create a new unit' ),
            'create_description' => __( 'Register a new unit and save it.' ),
            'edit_title' => __( 'Edit unit' ),
            'edit_description' => __( 'Modify  Unit.' ),
            'back_to_list' => __( 'Return to Units' ),
        ];
    }

    /**
     * Check whether a feature is enabled
     * @param string $feature The feature to check
     * @return bool
     **/
    public function isEnabled( $feature ): bool
    {
        return false; // by default
    }

    /**
     * Hook to modify the query for the unit list
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function hook( $query ): void
    {
        $query->addSelect( [
            'assigned_products' => function ( $query ) {
                $query->selectRaw( 'COUNT(*)' )
                    ->from( 'nexopos_products_unit_quantities' )
                    ->whereColumn( 'nexopos_products_unit_quantities.unit_id', '=', 'nexopos_units.id' );
            },
        ] );
    }

    /**
     * Fields for the unit form
     *
     * @param  Unit|null $entry
     * @return array of fields
     */
    public function getForm( $entry = null )
    {
        return CrudForm::form(
            main: FormInput::text(
                label: __( 'Name' ),
                name: 'name',
                description: __( 'Provide a name tot he resource.' ),
                validation: 'required',
                value: $entry->name ?? '',
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    label: __( 'General' ),
                    identifier: 'general',
                    fields: CrudForm::fields(
                        FormInput::text(
                            label: __( 'Identifier' ),
                            name: 'identifier',
                            description: __( 'Provide a unique value for this unit. Might be composed from a name but shouldn\'t include space or special characters.' ),
                            validation: 'unique:' . Hook::filter( 'ns-table-name', 'nexopos_units' ) . ',identifier' . ( $entry !== null ? ',' . $entry->id : '' ),
                            value: $entry->identifier ?? '',
                        ),
                        FormInput::media(
                            label: __( 'Preview URL' ),
                            name: 'preview_url',
                            description: __( 'Preview of the unit.' ),
                            value: $entry->preview_url ?? '',
                        ),
                        FormInput::text(
                            label: __( 'Value' ),
                            name: 'value',
                            description: __( 'Define the value of the unit.' ),
                            validation: 'required',
                            value: $entry->value ?? '',
                        ),
                        FormInput::searchSelect(
                            component: 'nsCrudForm',
                            props: UnitGroupCrud::getFormConfig(),
                            name: 'group_id',
                            validation: 'required',
                            options: Helper::toJsOptions( UnitGroup::get(), [ 'id', 'name' ] ),
                            label: __( 'Group' ),
                            description: __( 'Define to which group the unit should be assigned.' ),
                            value: $entry->group_id ?? '',
                        ),
                        FormInput::switch(
                            name: 'base_unit',
                            validation: 'required',
                            options: Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
                            label: __( 'Base Unit' ),
                            description: __( 'Determine if the unit is the base unit from the group.' ),
                            value: $entry ? ( $entry->base_unit ? 1 : 0 ) : 0,
                        ),
                        FormInput::textarea(
                            name: 'description',
                            label: __( 'Description' ),
                            description: __( 'Provide a short description about the unit.' ),
                            value: $entry->description ?? '',
                        )
                    )
                )
            )
        );
    }

    /**
     * Filter POST input fields
     *
     * @param array $inputs fields
     * @return array $inputs
     */
    public function filterPostInputs( $inputs )
    {
        if ( empty( $inputs[ 'identifier' ] ) ) {
            $inputs[ 'identifier' ] = Str::slug( $inputs[ 'name' ] );
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param array $inputs fields
     * @param Unit $entry
     * @return array $inputs
     */
    public function filterPutInputs( $inputs, Unit $entry )
    {
        if ( empty( $inputs[ 'identifier' ] ) ) {
            $inputs[ 'identifier' ] = Str::slug( $inputs[ 'name' ] );
        }

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
        return $request;
    }

    /**
     * After saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function afterPost( $request, Unit $entry )
    {
        return $request;
    }

    /**
     * get a specific parameter of the CRUD class
     *
     * @param  string $param
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
     * @param Unit $entry
     * @return void
     */
    public function beforePut( $request, Unit $entry )
    {
        return $request;
    }

    /**
     * After updating a record
     *
     * @param Request $request
     * @param Unit $entry
     * @return void
     */
    public function afterPut( $request, Unit $entry )
    {
        return $request;
    }

    /**
     * Before Delete
     *
     * @param string $namespace
     * @param int $id
     * @param Unit $model
     * @return void
     */
    public function beforeDelete( $namespace, $id, $model )
    {
        // ...
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column( 
                identifier: 'name', 
                label: __( 'Name' ),
                sort: false
            ),
            CrudTable::column( 'value', __( 'Value' ) ),
            CrudTable::column( 'base_unit', __( 'Base Unit' ) ),
            CrudTable::column( 'assigned_products', __( 'Assigned Products' ) ),
            CrudTable::column( 'group_name', __( 'Group' ) ),
            CrudTable::column( 'user_username', __( 'Author' ) ),
            CrudTable::column( 'created_at', __( 'Created At' ) )
        );
    }

    /**
     * Define actions
     *
     * @param CrudEntry $entry
     * @return CrudEntry
     */
    public function setActions( CrudEntry $entry ): CrudEntry
    {
        $entry->base_unit = (bool) $entry->base_unit ? __( 'Yes' ) : __( 'No' );
        // you can make changes here
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . 'units' . '/edit/' . $entry->id )
        );

        // Snippet 2
        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.units/' . $entry->id ),
            confirm: [
                'message' => __( 'Would you like to delete this ?' ),
            ]
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param Request $request
     * @return false|array
     */
    public function bulkAction( Request $request )
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        $user = app()->make( UsersService::class );
        if ( ! $user->is( [ 'admin', 'supervisor' ] ) ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'You\'re not allowed to do this operation' ),
            ], 403 );
        }

        if ( $request->input( 'action' ) == 'delete_selected' ) {
            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity = $this->model::find( $id );
                if ( $entity instanceof Unit ) {
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
            'list' => ns()->url( 'dashboard/' . 'units' ),
            'create' => ns()->url( 'dashboard/' . 'units/create' ),
            'edit' => ns()->url( 'dashboard/' . 'units/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.units' ),
            'put' => ns()->url( 'api/crud/' . 'ns.units/{id}' . '' ),
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
