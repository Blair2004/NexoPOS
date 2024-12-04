<?php

namespace App\Crud;

use App\Classes\CrudForm;
use App\Classes\FormInput;
use App\Exceptions\NotAllowedException;
use App\Models\PaymentType;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use TorMorten\Eventy\Facades\Events as Hook;

class PaymentTypeCrud extends CrudService
{
    /**
     * Define the autoload status
     */
    const AUTOLOAD = true;

    /**
     * Define the identifier
     */
    const IDENTIFIER = 'ns.payments-types';

    /**
     * define the base table
     *
     * @param  string
     */
    protected $table = 'nexopos_payments_types';

    /**
     * default slug
     *
     * @param  string
     */
    protected $slug = 'orders/payments-types';

    /**
     * Define namespace
     *
     * @param  string
     */
    protected $namespace = 'ns.payments-types';

    /**
     * Model Used
     *
     * @param  string
     */
    protected $model = PaymentType::class;

    /**
     * Define permissions
     *
     * @param  array
     */
    protected $permissions = [
        'create' => 'nexopos.manage-payments-types',
        'read' => 'nexopos.manage-payments-types',
        'update' => 'nexopos.manage-payments-types',
        'delete' => 'nexopos.manage-payments-types',
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param  array
     */
    public $relations = [
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_payments_types.author' ],
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
            'list_title' => __( 'Payment Types List' ),
            'list_description' => __( 'Display all payment types.' ),
            'no_entry' => __( 'No payment types has been registered' ),
            'create_new' => __( 'Add a new payment type' ),
            'create_title' => __( 'Create a new payment type' ),
            'create_description' => __( 'Register a new payment type and save it.' ),
            'edit_title' => __( 'Edit payment type' ),
            'edit_description' => __( 'Modify  Payment Type.' ),
            'back_to_list' => __( 'Return to Payment Types' ),
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
        return CrudForm::form(
            main: FormInput::text(
                label: __( 'Label' ),
                name: 'label',
                value: $entry->label ?? '',
                validation: 'required',
                description: __( 'Provide a label to the resource.' ),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: CrudForm::fields(
                        FormInput::switch(
                            options: Helper::kvToJsOptions( [ __( 'No' ), __( 'Yes' ) ] ),
                            name: 'active',
                            label: __( 'Active' ),
                            validation: 'required',
                            value: $entry->active ?? '',
                        ),
                        FormInput::number(
                            name: 'priority',
                            label: __( 'Priority' ),
                            value: $entry->priority ?? '',
                            description: __( 'Define the order for the payment. The lower the number is, the first it will display on the payment popup. Must start from "0".' ),
                        ),
                        FormInput::text(
                            name: 'identifier',
                            label: __( 'Identifier' ),
                            value: $entry->identifier ?? '',
                        ),
                        FormInput::textarea(
                            name: 'description',
                            label: __( 'Description' ),
                            value: $entry->description ?? '',
                        ),
                    )
                )
            )
        );
    }

    /**
     * Filter POST input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPostInputs( $inputs )
    {
        $payment = PaymentType::where( 'identifier', $inputs[ 'identifier' ] )->first();

        $inputs[ 'priority' ] = empty( $inputs[ 'priority' ] ) ? 0 : $inputs[ 'priority' ];
        $inputs[ 'priority' ] = (int) $inputs[ 'priority' ] < 0 ? 0 : $inputs[ 'priority' ];

        if ( empty( $inputs[ 'identifier' ] ) ) {
            $inputs[ 'identifier' ] = Str::slug( $inputs[ 'label' ] );
        }

        if ( $payment instanceof PaymentType ) {
            throw new NotAllowedException( __( 'A payment type having the same identifier already exists.' ) );
        }

        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param  array of fields
     * @return array of fields
     */
    public function filterPutInputs( $inputs, PaymentType $entry )
    {
        $inputs[ 'priority' ] = empty( $inputs[ 'priority' ] ) ? 0 : $inputs[ 'priority' ];
        $inputs[ 'priority' ] = (int) $inputs[ 'priority' ] < 0 ? 0 : $inputs[ 'priority' ];

        if ( empty( $inputs[ 'identifier' ] ) ) {
            $inputs[ 'identifier' ] = Str::slug( $inputs[ 'label' ] );
        }

        /**
         * the identifier should not
         * be edited for readonly payment type
         */
        if ( $entry->readonly ) {
            $inputs[ 'identifier' ] = $entry->identifier;
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
        if ( $this->permissions[ 'create' ] !== false ) {
            ns()->restrict( $this->permissions[ 'create' ] );
        } else {
            throw new NotAllowedException;
        }

        Cache::forget( 'nexopos.pos.payments' );
        Cache::forget( 'nexopos.pos.payments-key' );

        return $request;
    }

    /**
     * After saving a record
     *
     * @param  Request $request
     * @return void
     */
    public function afterPost( $request, PaymentType $entry )
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

        Cache::forget( 'nexopos.pos.payments' );
        Cache::forget( 'nexopos.pos.payments-key' );

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
        if ( $namespace == 'ns.payments-types' ) {
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

            if ( $model->readonly ) {
                throw new NotAllowedException( __( 'Unable to delete a read-only payments type.' ) );
            }

            Cache::forget( 'nexopos.pos.payments' );
            Cache::forget( 'nexopos.pos.payments-key' );
        }
    }

    /**
     * Define Columns
     */
    public function getColumns(): array
    {
        return [
            'identifier' => [
                'label' => __( 'Identifier' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'label' => [
                'label' => __( 'Label' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'active' => [
                'label' => __( 'Active' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'priority' => [
                'label' => __( 'Priority' ),
                '$direction' => '',
                '$sort' => true,
            ],
            'created_at' => [
                'label' => __( 'Created On' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'readonly' => [
                'label' => __( 'Readonly' ),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __( 'Author' ),
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
        $entry->readonly = $entry->readonly ? __( 'Yes' ) : __( 'No' );
        $entry->active = $entry->active ? __( 'Yes' ) : __( 'No' );

        // you can make changes here
        $entry->action(
            identifier: 'edit',
            label: __( 'Edit' ),
            type: 'GOTO',
            url: ns()->url( '/dashboard/' . $this->slug . '/edit/' . $entry->id ),
        );

        $entry->action(
            identifier: 'delete',
            label: __( 'Delete' ),
            type: 'DELETE',
            url: ns()->url( '/api/crud/ns.payments-types/' . $entry->id ),
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

                if ( $entity->readonly ) {
                    $status[ 'error' ]++;
                    break;
                }

                if ( $entity instanceof PaymentType ) {
                    Cache::forget( 'nexopos.pos.payments' );
                    Cache::forget( 'nexopos.pos.payments-key' );

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
            'list' => ns()->url( 'dashboard/' . 'orders/payments-types' ),
            'create' => ns()->url( 'dashboard/' . 'orders/payments-types/create' ),
            'edit' => ns()->url( 'dashboard/' . 'orders/payments-types/edit/' ),
            'post' => ns()->url( 'api/crud/' . 'ns.payments-types' ),
            'put' => ns()->url( 'api/crud/' . 'ns.payments-types/{id}' . '' ),
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
