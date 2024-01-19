<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Casts\DiscountTypeCast;
use App\Exceptions\NotAllowedException;
use App\Models\OrderCoupon;
use App\Models\User;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Users;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class CouponOrderHistoryCrud extends CrudService
{
    const AUTOLOAD = true;

    const IDENTIFIER = 'ns.coupons-orders-history';

    /**
     * define the base table
     *
     * @param string
     */
    protected $table = 'nexopos_orders_coupons';

    /**
     * default slug
     *
     * @param string
     */
    protected $slug = '/';

    /**
     * Define namespace
     *
     * @param string
     */
    protected $namespace = 'ns.coupons-orders-history';

    /**
     * Model Used
     *
     * @param string
     */
    protected $model = OrderCoupon::class;

    /**
     * Define permissions
     *
     * @param array
     */
    protected $permissions = [
        'create' => false,
        'read' => true,
        'update' => false,
        'delete' => false,
    ];

    protected $casts = [
        'type' => DiscountTypeCast::class,
        'value' => CurrencyCast::class,
        'created_at' => DateCast::class,
    ];

    /**
     * Adding relation
     * Example : [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders.author' ]
     *
     * @param array
     */
    public $relations = [
        [ 'nexopos_users as user', 'user.id', '=', 'nexopos_orders_coupons.author' ],
        [ 'nexopos_orders as order', 'order.id', '=', 'nexopos_orders_coupons.order_id' ],
        [ 'nexopos_users as customer', 'customer.id', '=', 'order.customer_id' ],
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
     * Restrict columns you retreive from relation.
     * Should be an array of associative keys, where
     * keys are either the related table or alias name.
     * Example : [
     *      'user'  =>  [ 'username' ], // here the relation on the table nexopos_users is using "user" as an alias
     * ]
     */
    public $pick = [
        'user' => [ 'username' ],
        'order' => [ 'id', 'code' ],
        'customer' => [ 'username', 'first_name', 'last_name' ],
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
     * Define Constructor
     */
    public function __construct()
    {
        parent::__construct();

        Hook::addFilter($this->namespace . '-crud-actions', [ $this, 'addActions' ], 10, 2);
    }

    public function hook($query): void
    {
        $query->where('coupon_id', request()->query('coupon_id'));
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
            'list_title' => __('Coupon Order Histories List'),
            'list_description' => __('Display all coupon order histories.'),
            'no_entry' => __('No coupon order histories has been registered'),
            'create_new' => __('Add a new coupon order history'),
            'create_title' => __('Create a new coupon order history'),
            'create_description' => __('Register a new coupon order history and save it.'),
            'edit_title' => __('Edit coupon order history'),
            'edit_description' => __('Modify  Coupon Order History.'),
            'back_to_list' => __('Return to Coupon Order Histories'),
        ];
    }

    /**
     * Fields
     *
     * @param object/null
     * @return array of field
     */
    public function getForm($entry = null)
    {
        return [
            'main' => [
                'label' => __('Name'),
                // 'name'          =>  'name',
                // 'value'         =>  $entry->name ?? '',
                'description' => __('Provide a name to the resource.'),
            ],
            'tabs' => [
                'general' => [
                    'label' => __('General'),
                    'fields' => [
                        [
                            'type' => 'text',
                            'name' => 'id',
                            'label' => __('Id'),
                            'value' => $entry->id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'code',
                            'label' => __('Code'),
                            'value' => $entry->code ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'name',
                            'label' => __('Name'),
                            'value' => $entry->name ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'customer_coupon_id',
                            'label' => __('Customer_coupon_id'),
                            'value' => $entry->customer_coupon_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'order_id',
                            'label' => __('Order_id'),
                            'value' => $entry->order_id ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'type',
                            'label' => __('Type'),
                            'value' => $entry->type ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'discount_value',
                            'label' => __('Discount_value'),
                            'value' => $entry->discount_value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'minimum_cart_value',
                            'label' => __('Minimum_cart_value'),
                            'value' => $entry->minimum_cart_value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'maximum_cart_value',
                            'label' => __('Maximum_cart_value'),
                            'value' => $entry->maximum_cart_value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'limit_usage',
                            'label' => __('Limit_usage'),
                            'value' => $entry->limit_usage ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'value',
                            'label' => __('Value'),
                            'value' => $entry->value ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'author',
                            'label' => __('Author'),
                            'value' => $entry->author ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'uuid',
                            'label' => __('Uuid'),
                            'value' => $entry->uuid ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'created_at',
                            'label' => __('Created_at'),
                            'value' => $entry->created_at ?? '',
                        ], [
                            'type' => 'text',
                            'name' => 'updated_at',
                            'label' => __('Updated_at'),
                            'value' => $entry->updated_at ?? '',
                        ],                     ],
                ],
            ],
        ];
    }

    /**
     * Filter POST input fields
     *
     * @param array of fields
     * @return array of fields
     */
    public function filterPostInputs($inputs)
    {
        return $inputs;
    }

    /**
     * Filter PUT input fields
     *
     * @param array of fields
     * @return array of fields
     */
    public function filterPutInputs($inputs, OrderCoupon $entry)
    {
        return $inputs;
    }

    /**
     * Before saving a record
     *
     * @param Request $request
     * @return void
     */
    public function beforePost($request)
    {
        if ($this->permissions[ 'create' ] !== false) {
            ns()->restrict($this->permissions[ 'create' ]);
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After saving a record
     *
     * @param Request $request
     * @return void
     */
    public function afterPost($request, OrderCoupon $entry)
    {
        return $request;
    }

    /**
     * get
     *
     * @param string
     * @return mixed
     */
    public function get($param)
    {
        switch ($param) {
            case 'model': return $this->model;
                break;
        }
    }

    /**
     * Before updating a record
     *
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function beforePut($request, $entry)
    {
        if ($this->permissions[ 'update' ] !== false) {
            ns()->restrict($this->permissions[ 'update' ]);
        } else {
            throw new NotAllowedException;
        }

        return $request;
    }

    /**
     * After updating a record
     *
     * @param Request $request
     * @param object entry
     * @return void
     */
    public function afterPut($request, $entry)
    {
        return $request;
    }

    /**
     * Before Delete
     *
     * @return void
     */
    public function beforeDelete($namespace, $id, $model)
    {
        if ($namespace == 'ns.coupons-orders-hitory') {
            /**
             *  Perform an action before deleting an entry
             *  In case something wrong, this response can be returned
             *
             *  return response([
             *      'status'    =>  'danger',
             *      'message'   =>  __( 'You\re not allowed to do that.' )
             *  ], 403 );
             **/
            if ($this->permissions[ 'delete' ] !== false) {
                ns()->restrict($this->permissions[ 'delete' ]);
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
                'label' => __('Name'),
                '$direction' => '',
                '$sort' => false,
            ],
            'code' => [
                'label' => __('Code'),
                '$direction' => '',
                '$sort' => false,
            ],
            'customer_first_name' => [
                'label' => __('Customer'),
                '$direction' => '',
                '$sort' => false,
            ],
            'order_code' => [
                'label' => __('Order'),
                '$direction' => '',
                '$sort' => false,
            ],
            'type' => [
                'label' => __('Type'),
                '$direction' => '',
                '$sort' => false,
            ],
            'discount_value' => [
                'label' => __('Discount'),
                '$direction' => '',
                '$sort' => false,
            ],
            'value' => [
                'label' => __('Value'),
                '$direction' => '',
                '$sort' => false,
            ],
            'user_username' => [
                'label' => __('Author'),
                '$direction' => '',
                '$sort' => false,
            ],
            'created_at' => [
                'label' => __('Created At'),
                '$direction' => '',
                '$sort' => false,
            ],
        ];
    }

    /**
     * Define actions
     */
    public function addActions(CrudEntry $entry, $namespace)
    {
        /**
         * Declaring entry actions
         */
        $entry->action(
            label: __('Edit'),
            identifier: 'edit',
            url: ns()->url('/dashboard/' . $this->slug . '/edit/' . $entry->id)
        );

        $entry->action(
            label: __('Delete'),
            identifier: 'delete',
            url: ns()->url('/api/crud/ns.coupons-orders-hitory/' . $entry->id),
            type: 'DELETE',
            confirm: [
                'message' => __('Would you like to delete this?'),
            ]
        );

        return $entry;
    }

    /**
     * Bulk Delete Action
     *
     * @param  object Request with object
     * @return  false/array
     */
    public function bulkAction(Request $request)
    {
        /**
         * Deleting licence is only allowed for admin
         * and supervisor.
         */
        if ($request->input('action') == 'delete_selected') {
            /**
             * Will control if the user has the permissoin to do that.
             */
            if ($this->permissions[ 'delete' ] !== false) {
                ns()->restrict($this->permissions[ 'delete' ]);
            } else {
                throw new NotAllowedException;
            }

            $status = [
                'success' => 0,
                'failed' => 0,
            ];

            foreach ($request->input('entries') as $id) {
                $entity = $this->model::find($id);
                if ($entity instanceof OrderCoupon) {
                    $entity->delete();
                    $status[ 'success' ]++;
                } else {
                    $status[ 'failed' ]++;
                }
            }

            return $status;
        }

        return Hook::filter($this->namespace . '-catch-action', false, $request);
    }

    /**
     * get Links
     *
     * @return array of links
     */
    public function getLinks(): array
    {
        return [
            'list' => ns()->url('dashboard/' . '/'),
            'create' => ns()->url('dashboard/' . '//create'),
            'edit' => ns()->url('dashboard/' . '//edit/'),
            'post' => ns()->url('api/crud/' . 'ns.coupons-orders-hitory'),
            'put' => ns()->url('api/crud/' . 'ns.coupons-orders-hitory/{id}' . ''),
        ];
    }

    /**
     * Get Bulk actions
     *
     * @return array of actions
     **/
    public function getBulkActions(): array
    {
        return Hook::filter($this->namespace . '-bulk', [
            [
                'label' => __('Delete Selected Groups'),
                'identifier' => 'delete_selected',
                'url' => ns()->route('ns.api.crud-bulk-actions', [
                    'namespace' => $this->namespace,
                ]),
            ],
        ]);
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
