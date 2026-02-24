---
applyTo: '**'
---

# NexoPOS CRUD System Guide (Accurate)

The NexoPOS CRUD (Create, Read, Update, Delete) system provides a powerful framework for managing data entities. This guide is based on actual CRUD implementations in the codebase.

## Architecture Overview

The CRUD system consists of:

- **CrudService**: Base service class (`app/Services/CrudService.php`)
- **CrudEntry**: Wrapper class for individual data entries (`app/Services/CrudEntry.php`)
- **CrudController**: HTTP controller (`app/Http/Controllers/Dashboard/CrudController.php`)
- **CrudTable**: Helper for table configuration (`app/Classes/CrudTable.php`)
- **CrudForm**: Helper for form configuration (`app/Classes/CrudForm.php`)
- **FormInput**: Helper for field configuration (`app/Classes/FormInput.php`)

## Core CRUD Class Structure

### Essential Constants and Properties

```php
<?php

namespace App\Crud;

use App\Services\CrudService;
use App\Services\CrudEntry;

class ExampleCrud extends CrudService
{
    /**
     * Auto-register CRUD class
     */
    const AUTOLOAD = true;

    /**
     * Unique identifier for the CRUD
     */
    const IDENTIFIER = 'ns.example';

    /**
     * Database table name
     */
    protected $table = 'nexopos_examples';

    /**
     * Route slug for dashboard URLs
     */
    protected $slug = 'examples';

    /**
     * Unique namespace identifier
     */
    protected $namespace = 'ns.examples';

    /**
     * Eloquent model class
     */
    protected $model = Example::class;

    /**
     * Permissions configuration
     */
    protected $permissions = [
        'create' => 'nexopos.create.examples',
        'read' => 'nexopos.read.examples',
        'update' => 'nexopos.update.examples',
        'delete' => 'nexopos.delete.examples',
    ];

    /**
     * Display options column before data columns
     */
    protected $prependOptions = false;

    /**
     * Show/hide the options column
     */
    protected $showOptions = true;
}
```

## Database Relations

### Standard Relations

Relations are defined in the `$relations` property:

```php
public $relations = [
    // Simple join
    ['nexopos_users as author', 'nexopos_examples.author_id', '=', 'author.id'],
    
    // Left join
    'leftJoin' => [
        ['nexopos_categories as category', 'nexopos_examples.category_id', '=', 'category.id'],
    ],
];
```

### Picking Specific Columns

Use the `$pick` property to restrict columns from related tables:

```php
public $pick = [
    'author' => ['username', 'email'],
    'category' => ['name'],
];
```

**Without `$pick`**: All columns from related tables are included.  
**With `$pick`**: Only specified columns are retrieved, with prefixed names like `author_username`, `category_name`.

## Column Configuration

### Using CrudTable Helper

The `getColumns()` method defines table columns:

```php
use App\Classes\CrudTable;

public function getColumns(): array
{
    return CrudTable::columns(
        CrudTable::column(
            label: __('Name'),
            identifier: 'name',
            width: '200px'
        ),
        CrudTable::column(
            label: __('Status'),
            identifier: 'status',
            width: '100px'
        ),
        CrudTable::column(
            label: __('Author'),
            identifier: 'author_username'
        ),
        CrudTable::column(
            label: __('Created'),
            identifier: 'created_at',
            width: '150px'
        ),
    );
}
```

### Legacy Array Format

```php
public function getColumns(): array
{
    return [
        'name' => [
            'label' => __('Name'),
            '$direction' => '',      // Sort direction: '', 'asc', 'desc'
            '$sort' => false,        // Enable sorting
            'width' => '200px',      // Column width
        ],
        'status' => [
            'label' => __('Status'),
            '$direction' => '',
            '$sort' => true,
        ],
    ];
}
```

## Row Actions Configuration

### Using CrudEntry Methods

The `setActions()` method defines row-level actions:

```php
public function setActions(CrudEntry $entry): CrudEntry
{
    // Edit action - navigate to edit page
    $entry->action(
        identifier: 'edit',
        label: '<i class="mr-2 las la-edit"></i> ' . __('Edit'),
        type: 'GOTO',
        url: ns()->url('/dashboard/' . $this->slug . '/edit/' . $entry->id)
    );

    // Preview in popup
    $entry->action(
        identifier: 'preview',
        label: '<i class="mr-2 las la-eye"></i> ' . __('Preview'),
        type: 'POPUP',
        url: ns()->url('/dashboard/' . $this->slug . '/preview/' . $entry->id)
    );

    // Delete with confirmation
    $entry->action(
        identifier: 'delete',
        label: '<i class="mr-2 las la-trash"></i> ' . __('Delete'),
        type: 'DELETE',
        url: ns()->url('/api/crud/' . self::IDENTIFIER . '/' . $entry->id),
        confirm: [
            'message' => __('Would you like to delete this entry?'),
        ]
    );

    // GET request with confirmation
    $entry->action(
        identifier: 'approve',
        label: '<i class="mr-2 las la-check"></i> ' . __('Approve'),
        type: 'GET',
        url: ns()->url('/api/examples/' . $entry->id . '/approve'),
        confirm: [
            'message' => __('Approve this entry?'),
        ]
    );

    return $entry;
}
```

### Action Types

- **`GOTO`**: Navigate to URL (no confirmation needed)
- **`POPUP`**: Open URL in popup modal
- **`DELETE`**: HTTP DELETE request (requires confirmation)
- **`GET`**: HTTP GET request (requires confirmation)
- **`POST`**: HTTP POST request (requires confirmation)

### Adding CSS Classes to Rows

```php
public function setActions(CrudEntry $entry): CrudEntry
{
    // Add CSS class based on status
    $entry->addClass(match($entry->status) {
        'active' => 'success border',
        'pending' => 'info border',
        'disabled' => 'error border',
        default => ''
    });

    // Or using $cssClass property
    $entry->{'$cssClass'} = 'border text-sm';

    return $entry;
}
```

### Accessing Raw Values

```php
public function setActions(CrudEntry $entry): CrudEntry
{
    // Access original database value before casting
    $rawStatus = $entry->getOriginalValue('status');
    
    // Store for conditional logic
    $entry->rawStatus = $rawStatus;
    
    // Apply conditional actions
    if ($rawStatus === 'pending') {
        $entry->action(
            identifier: 'approve',
            label: __('Approve'),
            // ...
        );
    }

    return $entry;
}
```

## Bulk Actions

### Definition

```php
public function getBulkActions(): array
{
    return [
        [
            'label' => __('Delete Selected'),
            'identifier' => 'delete_selected',
            'url' => ns()->route('ns.api.crud-bulk-actions', [
                'namespace' => $this->namespace,
            ]),
        ],
        [
            'label' => __('Export Selected'),
            'identifier' => 'export_selected',
            'url' => ns()->route('ns.api.crud-export', [
                'namespace' => $this->namespace,
            ]),
        ],
    ];
}
```

### Handling Bulk Actions

```php
use Illuminate\Http\Request;
use App\Exceptions\NotAllowedException;

public function bulkAction(Request $request): array
{
    $action = $request->input('action');
    
    if ($action === 'delete_selected') {
        // Check permissions
        if ($this->permissions['delete'] !== false) {
            ns()->restrict($this->permissions['delete']);
        } else {
            throw new NotAllowedException();
        }

        $status = [
            'success' => 0,
            'error' => 0,
        ];

        foreach ($request->input('entries') as $id) {
            $entity = $this->model::find($id);
            if ($entity instanceof Example) {
                $entity->delete();
                $status['success']++;
            } else {
                $status['error']++;
            }
        }

        return $status;
    }

    // Allow hook to catch custom actions
    return Hook::filter($this->namespace . '-catch-action', false, $request);
}
```

## Query Customization

### Hook Method

Customize the query before execution:

```php
public function hook($query): void
{
    // Default ordering
    $query->orderBy('updated_at', 'desc');
    
    // Conditional filtering
    if (!empty(request()->query('status'))) {
        $query->where('status', request()->query('status'));
    }
    
    // Filter by user
    if (!ns()->allowedTo('view.all.examples')) {
        $query->where('author_id', auth()->id());
    }
}
```

### Query Filters

Define dynamic filters for the UI:

```php
use App\Services\Helper;

public function __construct()
{
    parent::__construct();
    
    $this->queryFilters = [
        [
            'type' => 'daterangepicker',
            'name' => 'created_at',
            'label' => __('Created Between'),
            'description' => __('Filter by creation date range'),
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'label' => __('Status'),
            'description' => __('Filter by status'),
            'options' => Helper::kvToJsOptions([
                'active' => __('Active'),
                'inactive' => __('Inactive'),
                'pending' => __('Pending'),
            ]),
        ],
        [
            'type' => 'text',
            'name' => 'name',
            'label' => __('Name'),
            'operator' => 'like',
            'description' => __('Search by name'),
        ],
    ];
}
```

## Lifecycle Hooks

### Create Hooks

```php
/**
 * Before creating entry
 */
public function beforePost(array $request): array
{
    $this->allowedTo('create');
    
    // Add automatic fields
    $request['author_id'] = auth()->id();
    $request['status'] = 'pending';
    
    return $request;
}

/**
 * After creating entry
 */
public function afterPost(array $request, Example $entry): array
{
    // Log activity
    // Send notifications
    // Create related records
    
    return $request;
}
```

### Update Hooks

```php
/**
 * Before updating entry
 */
public function beforePut(array $request, Example $entry): array
{
    $this->allowedTo('update');
    
    // Track changes
    $request['updated_by'] = auth()->id();
    
    return $request;
}

/**
 * After updating entry
 */
public function afterPut(array $request, Example $entry): array
{
    // Clear cache
    // Update related records
    
    return $request;
}
```

### Delete Hooks

```php
/**
 * Before deleting entry
 */
public function beforeDelete($namespace, $id, $model): void
{
    if ($namespace === self::IDENTIFIER) {
        if ($this->permissions['delete'] !== false) {
            ns()->restrict($this->permissions['delete']);
        } else {
            throw new NotAllowedException();
        }
        
        // Check for dependencies
        if ($model->hasRelatedRecords()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Cannot delete entry with dependencies'),
            ], 403);
        }
    }
}
```

## Labels Configuration

Define UI labels:

```php
use App\Classes\CrudTable;

public function getLabels(): array
{
    return CrudTable::labels(
        list_title: __('Examples List'),
        list_description: __('Display all examples.'),
        no_entry: __('No examples found'),
        create_new: __('Add New Example'),
        create_title: __('Create Example'),
        create_description: __('Register a new example'),
        edit_title: __('Edit Example'),
        edit_description: __('Modify example details'),
        back_to_list: __('Return to Examples')
    );
}
```

## Links Configuration

Define navigation URLs:

```php
public function getLinks(): array
{
    return [
        'list' => ns()->url('dashboard/' . $this->slug),
        'create' => ns()->url('dashboard/' . $this->slug . '/create'),
        'edit' => ns()->url('dashboard/' . $this->slug . '/edit/'),
        'post' => ns()->url('api/crud/' . $this->namespace),
        'put' => ns()->url('api/crud/' . $this->namespace . '/{id}'),
    ];
}
```

## Data Casting

Use casts to format data for display:

```php
use App\Casts\CurrencyCast;
use App\Casts\DateCast;

protected $casts = [
    'price' => CurrencyCast::class,
    'created_at' => DateCast::class,
    'status' => StatusCast::class,
];
```

## Complete Example: OrderCrud

Based on the actual `OrderCrud` implementation:

```php
<?php

namespace App\Crud;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use App\Casts\OrderDeliveryCast;
use App\Casts\OrderPaymentCast;
use App\Classes\CrudTable;
use App\Exceptions\NotAllowedException;
use App\Models\Order;
use App\Services\CrudEntry;
use App\Services\CrudService;
use App\Services\Helper;
use Illuminate\Http\Request;
use TorMorten\Eventy\Facades\Events as Hook;

class OrderCrud extends CrudService
{
    const AUTOLOAD = true;
    const IDENTIFIER = 'ns.orders';

    protected $table = 'nexopos_orders';
    protected $mainRoute = 'ns.orders';
    protected $namespace = 'ns.orders';
    protected $model = Order::class;
    protected $prependOptions = true;

    public $relations = [
        ['nexopos_users as author', 'nexopos_orders.author', '=', 'author.id'],
        ['nexopos_users as customer', 'nexopos_orders.customer_id', '=', 'customer.id'],
    ];

    public $pick = [
        'author' => ['username'],
        'customer' => ['first_name', 'phone'],
    ];

    protected $permissions = [
        'create' => 'nexopos.create.orders',
        'read' => 'nexopos.read.orders',
        'update' => 'nexopos.update.orders',
        'delete' => 'nexopos.delete.orders',
    ];

    protected $casts = [
        'total' => CurrencyCast::class,
        'tax_value' => CurrencyCast::class,
        'delivery_status' => OrderDeliveryCast::class,
        'payment_status' => OrderPaymentCast::class,
        'created_at' => DateCast::class,
    ];

    public function getColumns(): array
    {
        return CrudTable::columns(
            CrudTable::column(
                label: __('Code'),
                identifier: 'code',
                width: '170px'
            ),
            CrudTable::column(
                label: __('Type'),
                identifier: 'type',
                width: '100px'
            ),
            CrudTable::column(
                label: __('Customer'),
                identifier: 'customer_first_name',
                width: '100px'
            ),
            CrudTable::column(
                label: __('Payment'),
                identifier: 'payment_status',
                width: '150px'
            ),
            CrudTable::column(
                label: __('Total'),
                identifier: 'total',
                width: '100px'
            ),
            CrudTable::column(
                label: __('Created At'),
                identifier: 'created_at',
                width: '150px'
            ),
        );
    }

    public function setActions(CrudEntry $entry): CrudEntry
    {
        // Apply CSS class based on payment status
        $entry->{'$cssClass'} = match($entry->__raw->payment_status) {
            Order::PAYMENT_PAID => 'success border text-sm',
            Order::PAYMENT_UNPAID => 'danger border text-sm',
            Order::PAYMENT_VOID => 'error border text-sm',
            default => ''
        };

        $entry->action(
            identifier: 'invoice',
            label: '<i class="mr-2 las la-file-invoice-dollar"></i> ' . __('Invoice'),
            url: ns()->url('/dashboard/orders/invoice/' . $entry->id),
        );

        $entry->action(
            identifier: 'receipt',
            label: '<i class="mr-2 las la-receipt"></i> ' . __('Receipt'),
            url: ns()->url('/dashboard/orders/receipt/' . $entry->id),
        );

        $entry->action(
            identifier: 'delete',
            label: '<i class="mr-2 las la-trash"></i> ' . __('Delete'),
            type: 'DELETE',
            url: ns()->url('/api/crud/ns.orders/' . $entry->id),
            confirm: [
                'message' => __('Would you like to delete this order?'),
            ],
        );

        return $entry;
    }

    public function hook($query): void
    {
        if (empty(request()->query('direction'))) {
            $query->orderBy('id', 'desc');
        }
    }

    public function bulkAction(Request $request): array
    {
        if ($request->input('action') === 'delete_selected') {
            if ($this->permissions['delete'] !== false) {
                ns()->restrict($this->permissions['delete']);
            } else {
                throw new NotAllowedException();
            }

            $status = [
                'success' => 0,
                'error' => 0,
            ];

            foreach ($request->input('entries') as $id) {
                $entity = $this->model::find($id);
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
}
```

## Best Practices

### 1. Always Use Type Hints

```php
public function setActions(CrudEntry $entry): CrudEntry
public function beforePost(array $request): array
public function bulkAction(Request $request): array
```

### 2. Permission Checking

```php
// Use allowedTo helper for cleaner code
$this->allowedTo('create');

// Or manual check
if ($this->permissions['delete'] !== false) {
    ns()->restrict($this->permissions['delete']);
} else {
    throw new NotAllowedException();
}
```

### 3. Consistent Identifiers

```php
const IDENTIFIER = 'ns.examples';
protected $namespace = 'ns.examples';
```

### 4. Use CrudTable and CrudEntry Helpers

```php
// Modern approach
return CrudTable::columns(
    CrudTable::column(label: __('Name'), identifier: 'name')
);

$entry->action(identifier: 'edit', label: __('Edit'), ...);
```

### 5. Filter Hooks

```php
// Allow external customization
return Hook::filter($this->namespace . '-bulk', $actions);
return Hook::filter($this->namespace . '-catch-action', false, $request);
```

## Integration with Frontend

### Blade Template

```blade
@extends('layout.dashboard')

@section('layout.dashboard.body')
<div class="h-full flex-auto flex flex-col">
    @include('common.dashboard-header')
    <div class="px-4 flex-auto flex flex-col" id="crud-table-container">
        <ns-crud
            src="{{ ns()->url('api/crud/' . $namespace) }}"
            create-url="{{ $createUrl }}"
            namespace="{{ $namespace }}">
        </ns-crud>
    </div>
</div>
@endsection
```

### Route Definition

```php
Route::get('/dashboard/examples', [ExampleController::class, 'list'])
    ->name('ns.dashboard.examples')
    ->middleware('ns.permission:read.examples');
```

## Extending CRUD Forms with Custom Tabs (Module Pattern)

Modules can inject custom tabs into existing CRUD forms using the filter pattern. This is useful for adding module-specific functionality to core CRUD operations (e.g., UserCrud, ProductCrud).

### Pattern Overview

```php
// In your module's service provider or event class
use App\Crud\UserCrud;

UserCrud::filterMethod('getForm', [YourFilterClass::class, 'injectCustomTab']);
```

### Filter Method Structure

The filter receives the complete form array from the CRUD's `getForm()` method and can modify it before rendering:

```php
namespace Modules\YourModule\Filters;

class UserCrudFilter
{
    public static function injectCustomTab($form, $entry = null)
    {
        // Add your custom tab to the form
        $form['tabs']['custom_tab_key'] = [
            'label' => __m('Custom Tab', 'YourModule'),
            'component' => 'ns-custom-tab-component',  // Vue component
            'fields' => []  // Will be populated by component
        ];
        
        return $form;
    }
}
```

### Vue Component Implementation

The component receives the `tab` prop and manages its own state through the `fields` array:

```typescript
// Resources/ts/components/NsCustomTabComponent.ts
export default defineComponent({
    name: 'NsCustomTabComponent',
    props: ['tab'],
    
    data() {
        return {
            loading: false,
            fields: [{
                name: 'custom_data',
                value: {
                    // Your custom data structure
                    assigned_items: [],
                    default_item_id: null
                }
            }]
        };
    },
    
    mounted() {
        this.loadData();
        this.restoreState();
    },
    
    watch: {
        fields: {
            deep: true,
            handler() {
                // Emit changes to parent - saves state on tab switch
                this.$emit('changed', this.fields);
            }
        }
    },
    
    methods: {
        restoreState() {
            // Restore state from previous tab visit or edit mode
            if (this.tab.fields && this.tab.fields.length > 0) {
                this.fields = this.tab.fields;
            }
        },
        
        loadData() {
            // Load your initial data (stores, items, etc.)
            nsHttpClient.get('/api/your-endpoint').subscribe({
                next: (response) => {
                    // Process response
                },
                error: (error) => {
                    nsSnackBar.error(error.message);
                }
            });
        }
    },
    
    template: `
        <div class="p-4">
            <!-- Your custom UI -->
        </div>
    `
});
```

### Component Registration

Register the component globally in your module's main TypeScript file:

```typescript
// Resources/ts/main.ts
import NsCustomTabComponent from './components/NsCustomTabComponent';

if (typeof nsExtraComponents !== 'undefined') {
    nsExtraComponents['ns-custom-tab-component'] = NsCustomTabComponent;
}
```

### State Management with Fields Array

The `fields` array is **critical** for state persistence:

1. **Tab Switch Preservation**: When user switches tabs, your `fields` are auto-saved
2. **Edit Mode**: When editing an entry, `tab.fields` contains previous data
3. **Form Submission**: `fields` are submitted as part of the form data

**Example fields structure:**

```typescript
// Single field with complex data
fields: [{
    name: 'store_access_data',
    value: {
        assigned_stores: [1, 3, 5],
        default_store_id: 1,
        all_stores: false
    }
}]

// Or multiple fields
fields: [
    { name: 'store_1_access', value: true },
    { name: 'store_3_access', value: false },
    { name: 'default_store', value: 1 }
]
```

### Backend Processing

Handle the custom tab data in CRUD hooks:

```php
class YourCrud extends CrudService
{
    public function afterPost($inputs, $entry, $filteredInputs)
    {
        // Process custom tab data
        if (isset($inputs['custom_tab_key']['custom_data'])) {
            $data = $inputs['custom_tab_key']['custom_data'];
            $this->processCustomData($entry->id, $data);
        }
    }
    
    public function afterPut($inputs, $entry, $filteredInputs)
    {
        // Same for updates
        if (isset($inputs['custom_tab_key']['custom_data'])) {
            $data = $inputs['custom_tab_key']['custom_data'];
            $this->processCustomData($entry->id, $data);
        }
    }
}
```

### Complete Example: Store Access Tab

```php
// In ModuleServiceProvider::boot()
UserCrud::filterMethod('getForm', [UserCrudFilter::class, 'injectStoreAccessTab']);
```

```php
// Filters/UserCrudFilter.php
class UserCrudFilter
{
    public static function injectStoreAccessTab($form, $entry = null)
    {
        $form['tabs']['store_access'] = [
            'label' => __m('Store Access', 'NsMultiStore'),
            'component' => 'ns-store-access-tab',
            'fields' => []
        ];
        return $form;
    }
}
```

```typescript
// Component handles store assignments, checkboxes, default store
// Emits 'changed' event with fields array
// Parent CRUD form handles save/restore automatically
```

### Best Practices

1. ✅ **Always watch `fields`** with `deep: true` and emit `changed`
2. ✅ **Restore state in `mounted()`** from `this.tab.fields`
3. ✅ **Use single field with JSON** for complex data structures
4. ✅ **Handle loading states** while fetching data
5. ✅ **Validate before emitting** to prevent invalid state saves
6. ✅ **Register component globally** using `nsExtraComponents`
7. ✅ **Process in `afterPost/afterPut` hooks** on backend

### Validation in Custom Tabs

```typescript
mounted() {
    this.validation = new FormValidation();
    this.validation.createFields({
        custom_data: { validation: 'required' }
    });
}

watch: {
    fields: {
        deep: true,
        handler() {
            if (this.validation.validateFields(this.fields)) {
                this.$emit('changed', this.fields);
            }
        }
    }
}
```

This guide is based on actual CRUD implementations in NexoPOS and reflects the true patterns used in production code.
