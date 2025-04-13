<?php
namespace App\Crud;

use App\Casts\DriverStatusCast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\CrudEntry;
use App\Classes\CrudTable;
use App\Classes\CrudInput;
use App\Classes\CrudForm;
use App\Classes\CrudScope;
use App\Classes\Output;
use App\Events\UserAfterCreatedEvent;
use App\Exceptions\NotAllowedException;
use TorMorten\Eventy\Facades\Events as Hook;
use App\Models\Driver;
use App\Models\Role;
use App\Models\Scopes\DriverScope;
use App\Services\Helper;
use App\Services\UsersService;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

#[ CrudScope( DriverScope::class ) ]
class DriverCrud extends CrudService
{
    /**
     * Defines if the crud class should be automatically discovered.
     * If set to "true", no need register that class on the "CrudServiceProvider".
     */
    const AUTOLOAD = true;

    /**
     * define the base table
     * @param string
     */
    protected $table = 'nexopos_users';

    /**
     * default slug
     * @param string
     */
    protected $slug = 'drivers';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace = 'ns.drivers';

    /**
     * To be able to autoload the class, we need to define
     * the identifier on a constant.
     */
    const IDENTIFIER = 'ns.drivers';

    /**
     * Model Used
     * @param string
     */
    protected $model = Driver::class;

    /**
     * Define permissions
     * @param array
     */
    protected $permissions  =   [
        'create'    =>  true,
        'read'      =>  true,
        'update'    =>  true,
        'delete'    =>  true,
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
    public $relations   =  [
        [ 'nexopos_users as author', 'author.id', '=', 'nexopos_users.author' ]
    ];

    /**
     * all tabs mentionned on the tabs relations
     * are ignored on the parent model.
     */
    protected $tabsRelations    =   [
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
        'author'    =>  [ 'username' ]
    ];

    /**
     * Define where statement
     * @var array
    **/
    protected $listWhere = [];

    /**
     * Define where in statement
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
        'label' => 'name'
    ];

    /**
     * We're defining the default status cast for the 
     * user. Note that "status" is provided on the "hook" method belong. 
     * It's not an internal column for the driver table.
     */
    protected $casts    =   [
        'status'    =>  DriverStatusCast::class
    ];

    /**
     * Return the label used for the crud object.
    **/
    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title:  __( 'Drivers List' ),
            list_description:  __( 'Display all drivers.' ),
            no_entry:  __( 'No drivers has been registered' ),
            create_new:  __( 'Add a new driver' ),
            create_title:  __( 'Create a new driver' ),
            create_description:  __( 'Register a new driver and save it.' ),
            edit_title:  __( 'Edit driver' ),
            edit_description:  __( 'Modify  Driver.' ),
            back_to_list:  __( 'Return to Drivers' ),
        );
    }

    public function hook( $builder ): void
    {
        // ...
    }

    /**
     * Defines the forms used to create and update entries.
     * @param Driver $entry
     * @return array
     */
    public function getForm( Driver | null $entry = null ): array
    {
        return CrudForm::form(
            main: CrudInput::text(
                label: __( 'Username' ),
                name: 'username',
                validation: 'required',
                description: __( 'Provide a name to the resource.' ),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: CrudForm::fields(
                        CrudInput::switch(
                            label: __( 'Active' ),
                            options: Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
                            name: 'active',
                            validation: 'required',
                            description: __( 'Set wether the driver is active or not.' ),
                        ),
                        CrudInput::text(
                            label: __( 'Email' ),
                            name: 'email',
                            validation: 'required',
                            description: __( 'Provide the driver\'s email' )
                        ),
                        CrudInput::password(
                            label: __( 'Password' ),
                            name: 'password',
                            validation: 'required',
                            description: __( 'Set the driver password.' ),
                        ),
                        CrudInput::password(
                            label: __( 'Confirm password' ),
                            name: 'password',
                            validation: 'same:general.password',
                            description: __( 'Confirm the driver password.' ),
                        ),
                        CrudInput::text(
                            label: __( 'First Name' ),
                            name: 'first_name',
                            description: __( 'Set the driver first name.' ),
                        ),
                        CrudInput::text(
                            label: __( 'Last Name' ),
                            name: 'last_name',
                            description: __( 'Set the driver last name.' ),
                        ),
                        CrudInput::select(
                            label: __( 'Gender' ),
                            name: 'gender',
                            options: Helper::kvToJsOptions([
                                ''  =>  __( 'Not Defined' ),
                                'male'  => __( 'Male' ),
                                'female'    =>  __( 'Female' ),
                            ]),
                            description: __( 'Provide the driver\'s gender.' ),
                        ),
                        CrudInput::text(
                            label: __( 'Phone' ),
                            name: 'phone',
                            description: __( 'Set the driver phone number.' ),
                        ),
                        CrudInput::text(
                            label: __( 'Pobox' ),
                            name: 'pobox',
                            description: __( 'Set the driver PO Box.' ),
                        ),
                        CrudInput::date(
                            label: __( 'Birth_date' ),
                            name: 'birth_date',
                            description: __( 'Set the driver birth date.' ),
                        ),
                    )
                )
            )
        );
    }

    /**
     * Filter POST input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs ): array
    {
        $inputs[ 'author' ] = Auth::id();

        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs( array $inputs, Driver $entry )
    {
        $inputs[ 'author' ] = Auth::id();
        
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
    public function afterPost( array $request, Driver $entry ): array
    {
        $userService    =   app()->make( UsersService::class );
        $userService->createAttribute( $entry );
        $userService->setUserRole( 
            user: $entry,
            roles: [ Role::namespace( Role::DRIVER )->id ]
        );
        
        return $request;
    }


    /**
     * A shortcut and secure way to access
     * senstive value on a read only way.
     */
    public function get( string $param ): mixed
    {
        switch( $param ) {
            case 'model' : return $this->model ; break;
            default : return null; break;
        }
    }

    /**
     * Trigger actions that are executed before
     * the crud entry is updated.
     */
    public function beforePut( array $request, Driver $entry ): array
    {
        $this->allowedTo( 'update' );

        return $request;
    }

    /**
     * This trigger actions that are executed after
     * the crud entry is successfully updated.
     */
    public function afterPut( array $request, Driver $entry ): array
    {
        return $request;
    }

    /**
     * This triggers actions that will be executed ebfore
     * the crud entry is deleted.
     */
    public function beforeDelete( $namespace, $id, $model ): void
    {
        if ( $namespace == 'ns.drivers' ) {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' ),
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
     * Define columns and how it is structured.
     */
    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                identifier: 'username',
                label: __( 'Username' ),
            ),
            CrudTable::column(
                identifier: 'email',
                label: __( 'Email' ),
            ),
            CrudTable::column(
                identifier: 'status',
                label: __( 'Status' ),
            ),
            CrudTable::column(
                identifier: 'author_username',
                label: __( 'Author' ),
            ),
            CrudTable::column(
                identifier: 'created_at',
                label: __( 'Created_at' ),
            ),
        );
    }

    public function getTableFooter(Output $output)
    {
        $output->addView( 'pages.dashboard.drivers.footer' );
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
            identifier: 'assigned-orders',
            label: __( 'See Assigned Orders' ),
            url: ns()->url( '/dashboard/' . $this->slug . '/orders/' . $entry->id )
        );

        $entry->action(
            identifier: 'change-status',
            label: __( 'Change Status' ),
            type: 'POPUP',
        );
        
        $entry->action( 
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.drivers/' . $entry->id ),
            confirm: [
                'message'  =>  __( 'Would you like to delete this ?' ),
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
            if ( $this->permissions[ 'delete' ] !== false ) {
                ns()->restrict( $this->permissions[ 'delete' ] );
            } else {
                throw new NotAllowedException;
            }

            $status     =   [
                'success'   =>  0,
                'error'    =>  0
            ];

            foreach ( $request->input( 'entries' ) as $id ) {
                $entity     =   $this->model::find( $id );
                if ( $entity instanceof Driver ) {
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
     * Defines links used on the CRUD object.
     */
    public function getLinks(): array
    {
        return  CrudTable::links(
            list:  ns()->url( 'dashboard/' . 'drivers' ),
            create:  ns()->url( 'dashboard/' . 'drivers/create' ),
            edit:  ns()->url( 'dashboard/' . 'drivers/edit/' ),
            post:  ns()->url( 'api/crud/' . 'ns.drivers' ),
            put:  ns()->url( 'api/crud/' . 'ns.drivers/{id}' . '' ),
        );
    }

    /**
     * Defines the bulk actions.
    **/
    public function getBulkActions(): array
    {
        return Hook::filter( $this->namespace . '-bulk', [
            [
                'label'         =>  __( 'Delete Selected Entries' ),
                'identifier'    =>  'delete_selected',
                'url'           =>  ns()->route( 'ns.api.crud-bulk-actions', [
                    'namespace' =>  $this->namespace
                ])
            ]
        ]);
    }

    /**
     * Defines the export configuration.
    **/
    public function getExports(): array
    {
        return [];
    }
}
