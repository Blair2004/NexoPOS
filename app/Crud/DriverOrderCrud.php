<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Casts\NotDefinedCast;
use App\Casts\OrderDeliveryCast;
use App\Casts\OrderPaymentCast;
use App\Casts\OrderProcessCast;
use App\Casts\OrderTypeCast;
use Illuminate\Http\Request;
use App\Services\CrudService;
use App\Services\CrudEntry;
use App\Classes\CrudTable;
use App\Classes\CrudInput;
use App\Classes\CrudForm;
use App\Classes\CrudScope;
use App\Exceptions\NotAllowedException;
use TorMorten\Eventy\Facades\Events as Hook;
use App\Models\Order;
use App\Models\Scopes\DriverOrderScope;
use App\Classes\Output;

#[CrudScope(class: DriverOrderScope::class)]
class DriverOrderCrud extends CrudService
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
    protected $table = 'nexopos_orders';

    /**
     * default slug
     * @param string
     */
    protected $slug = 'drivers-orders';

    /**
     * Define namespace
     * @param string
     */
    protected $namespace = 'ns.drivers-orders';

    /**
     * To be able to autoload the class, we need to define
     * the identifier on a constant.
     */
    const IDENTIFIER = 'ns.drivers-orders';

    /**
     * Model Used
     * @param string
     */
    protected $model = Order::class;

    /**
     * Define permissions
     * @param array
     */
    protected $permissions  =   [
        'create'    =>  true,
        'read'      =>  true,
        'update'    =>  false,
        'delete'    =>  false,
    ];

    protected $casts = [
        'customer_phone' => NotDefinedCast::class,
        'total' => CurrencyCast::class,
        'tax_value' => CurrencyCast::class,
        'discount' => CurrencyCast::class,
        'change' => CurrencyCast::class,
        'shipping'  => CurrencyCast::class,
        'delivery_status' => OrderDeliveryCast::class,
        'process_status' => OrderProcessCast::class,
        'type' => OrderTypeCast::class,
        'payment_status' => OrderPaymentCast::class,
        'created_at' => DateCast::class,
        'updated_at' => DateCast::class,
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
    public $relations   =  [];

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
    public $pick = [];

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
     * Return the label used for the crud object.
     **/
    public function getLabels(): array
    {
        return CrudTable::labels(
            list_title: __('DriverOrders List'),
            list_description: __('Display all driverorders.'),
            no_entry: __('No driverorders has been registered'),
            create_new: __('Add a new driverorder'),
            create_title: __('Create a new driverorder'),
            create_description: __('Register a new driverorder and save it.'),
            edit_title: __('Edit driverorder'),
            edit_description: __('Modify  Driverorder.'),
            back_to_list: __('Return to DriverOrders'),
        );
    }

    /**
     * Defines the forms used to create and update entries.
     * @param Order $entry
     * @return array
     */
    public function getForm(Order | null $entry = null): array
    {
        return CrudForm::form(
            main: CrudInput::text(
                label: __('Name'),
                name: 'name',
                validation: 'required',
                description: __('Provide a name to the resource.'),
            ),
            tabs: CrudForm::tabs(
                CrudForm::tab(
                    identifier: 'general',
                    label: __('General'),
                    fields: CrudForm::fields(
                        CrudInput::text(
                            label: __('Id'),
                            name: 'id',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Description'),
                            name: 'description',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Code'),
                            name: 'code',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Title'),
                            name: 'title',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Type'),
                            name: 'type',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Payment_status'),
                            name: 'payment_status',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Process_status'),
                            name: 'process_status',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Delivery_status'),
                            name: 'delivery_status',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Discount'),
                            name: 'discount',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Discount_type'),
                            name: 'discount_type',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Support_instalments'),
                            name: 'support_instalments',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Discount_percentage'),
                            name: 'discount_percentage',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Shipping'),
                            name: 'shipping',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Shipping_rate'),
                            name: 'shipping_rate',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Shipping_type'),
                            name: 'shipping_type',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Total_without_tax'),
                            name: 'total_without_tax',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Subtotal'),
                            name: 'subtotal',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Total_with_tax'),
                            name: 'total_with_tax',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Total_coupons'),
                            name: 'total_coupons',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Total_cogs'),
                            name: 'total_cogs',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Total'),
                            name: 'total',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Tax_value'),
                            name: 'tax_value',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Products_tax_value'),
                            name: 'products_tax_value',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Tax_group_id'),
                            name: 'tax_group_id',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Tax_type'),
                            name: 'tax_type',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Tendered'),
                            name: 'tendered',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Change'),
                            name: 'change',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Final_payment_date'),
                            name: 'final_payment_date',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Total_instalments'),
                            name: 'total_instalments',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Customer_id'),
                            name: 'customer_id',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Note'),
                            name: 'note',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Note_visibility'),
                            name: 'note_visibility',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Author'),
                            name: 'author',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Uuid'),
                            name: 'uuid',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Register_id'),
                            name: 'register_id',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Voidance_reason'),
                            name: 'voidance_reason',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Driver_id'),
                            name: 'driver_id',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Created_at'),
                            name: 'created_at',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
                        ),
                        CrudInput::text(
                            label: __('Updated_at'),
                            name: 'updated_at',
                            validation: 'required',
                            description: __('Provide a name to the resource.'),
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
    public function filterPostInputs($inputs): array
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs(array $inputs, Order $entry)
    {
        return $inputs;
    }

    /**
     * Trigger actions that are executed before the
     * crud entry is created.
     */
    public function beforePost(array $request): array
    {
        $this->allowedTo('create');

        return $request;
    }

    /**
     * Trigger actions that will be executed 
     * after the entry has been created.
     */
    public function afterPost(array $request, Order $entry): array
    {
        return $request;
    }


    /**
     * A shortcut and secure way to access
     * senstive value on a read only way.
     */
    public function get(string $param): mixed
    {
        switch ($param) {
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
    public function beforePut(array $request, Order $entry): array
    {
        $this->allowedTo('update');

        return $request;
    }

    /**
     * This trigger actions that are executed after
     * the crud entry is successfully updated.
     */
    public function afterPut(array $request, Order $entry): array
    {
        return $request;
    }

    /**
     * This triggers actions that will be executed ebfore
     * the crud entry is deleted.
     */
    public function beforeDelete($namespace, $id, $model): void
    {
        if ($namespace == 'ns.drivers-orders') {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' ),
             *  ], 403 );
             **/
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
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
                identifier: 'code',
                label: __('Code'),
            ),
            CrudTable::column(
                identifier: 'type',
                label: __('Type'),
            ),
            CrudTable::column(
                identifier: 'payment_status',
                label: __('Payment Status'),
            ),
            CrudTable::column(
                identifier: 'process_status',
                label: __('Process Status'),
            ),
            CrudTable::column(
                identifier: 'delivery_status',
                label: __('Delivery Status'),
            ),
            CrudTable::column(
                identifier: 'shipping',
                label: __('Shipping'),
            ),
            CrudTable::column(
                identifier: 'total',
                label: __('Total'),
            ),
            CrudTable::column(
                identifier: 'change',
                label: __('Due'),
            ),
            CrudTable::column(
                identifier: 'created_at',
                label: __('Created On'),
            ),
        );
    }

    /**
     * Define row actions.
     */
    public function setActions(CrudEntry $entry): CrudEntry
    {
        if ( $entry->getRawValue( 'change' ) < 0 ) {
            $entry->change = ns()->currency->define( abs( $entry->getRawValue( 'change' ) ) )->format();
        }

        $entry->action(
            label: __( 'Deliver Order' ),
            identifier: 'change-delivery-status',
            permissions: [ 'nexopos.deliver.orders' ],
            type: 'POPUP'
        );

        return $entry;
    }


    /**
     * trigger actions that are executed
     * when a bulk actio is posted.
     */
    public function bulkAction(Request $request): array
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */

        if ($request->input('action') == 'delete_selected') {

            /**
             * Will control if the user has the permissoin to do that.
             */
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
            } else {
                throw new NotAllowedException;
            }

            $status     =   [
                'success'   =>  0,
                'error'    =>  0
            ];

            foreach ($request->input('entries') as $id) {
                $entity     =   $this->model::find($id);
                if ($entity instanceof Order) {
                    $entity->delete();
                    $status['success']++;
                } else {
                    $status['error']++;
                }
            }
            return $status;
        }

        return Hook::filter($this->namespace . '-catch-action', false, $request);
    }

    /**
     * Defines links used on the CRUD object.
     */
    public function getLinks(): array
    {
        return  CrudTable::links(
            list: ns()->url('dashboard/' . 'drivers-orders'),
            create: ns()->url('dashboard/' . 'drivers-orders/create'),
            edit: ns()->url('dashboard/' . 'drivers-orders/edit/'),
            post: ns()->url('api/crud/' . 'ns.drivers-orders'),
            put: ns()->url('api/crud/' . 'ns.drivers-orders/{id}' . ''),
        );
    }

    /**
     * Defines the bulk actions.
     **/
    public function getBulkActions(): array
    {
        return Hook::filter($this->namespace . '-bulk', [
            [
                'label'         =>  __('Update Status'),
                'identifier'    =>  'update_selected',
            ]
        ]);
    }

    public function getTableFooter( Output $output )
    {
        $output->addView( 'pages.dashboard.drivers-orders.footer' );
    }

    /**
     * Defines the export configuration.
     **/
    public function getExports(): array
    {
        return [];
    }
}
