# Introduction
The CRUD feature helps to manage components easilly by providing UI to create, read, update and delete components entities. 
This is usually created to help managing orders list, customers or anything that will requires to be created, read, updated and deleted.
The current documentation explains how the CRUD feature works on NexoPOS 4.x.

## Working Principle
The CRUD use backend and frontend functionnalities to works. We rely on Vue.js to create the frontend table and forms and on the backend to ensure
configuration, data validation & data saving. The feature has a built-in command that helps to create a CRUD components quickly from the CLI using the command : 

```php artisan make:crud```

This will start a Crud configuration assistant.

Once a CRUD component has been generated, it needs to be registered to be working. That is made on the [```App\Providers\CrudServiceProvider```](https://github.com/Blair2004/NexoPOS-v4/blob/master/app/Providers/CrudServiceProvider.php) on the `boot` method.
A Crud component must have a unique identifier (namespace). All default components are prefixed with "ns.". 
For example, the Crud Component responsible of managing the customers group is `ns.customers-groups`. A deeper guide on registering/extending existing Crud components will be provided later.

## Using Crud API
As every crud class inherit from the `CrudService::class`, you now can just return a single line of code from your controller to either render a table or a form. 

### Create Table with CrudService::table()
That static method will be the Crud object call the necessary properties to build your CRUD UI. Here is concretely how you can do that : 

```php
  // ...
  public function listUsers()
  {
      return UsersCrud::table();
  }
```

Here we assume `listUsers` is a method of a controller. The class `UsersCrud` must extends `App\Services\CrudService::class` (already extended when generated using the above command). If you would like to change things like the title, description, you can still perform this from your Crud class.

### Create Form with CrudService::form( $model = null, $config = [])

The `form` method renders a form. It will build that form using the internal Crud properties like the `getLinks()` method, `permissions` parameters (to ensure the user has the right to perform that action) and much more. In order to adjust your Crud resource, you need to perform your changes directly within your class.

```php
    // ...
    public function createUser()
    {
        return UsersCrud::form();
    }
```

If we would like to edit a user, we would have used similar call : 

```php
    // ...
    public function updateUser( User $user )
    {
        return UserCrud::form( $user );
    }
```

The method `form` takes a second parameter (array) that allow you to directly perform quick modification to some values that normally should be fetched from the Crud instance. For example, you can change the `returnUrl` or the `title`. Here is the list of supported attributes : 

| Attribute      | Description |
|----------------|-------------|
| title          | Adjut the resource title  |
| description    | Adjust the resource description  |
| src            | Change the source Url  |
| returnUrl      | Change the returnUrl that takes back to the table  |
| submitUrl      | Change the submit Url, used to submit the form  |
| submitMethod   | Change the method, either "post" or "put" if a Model object is provided  |

## Using the Vue.js Components

The following instructions describes how to manually create the forms and table. You might not need it if you use the above API. It can however be used to better understand how it works.

You can use Vue.js components for the table and the forms. You should note that, you're not forced to use those as you can create your own components. By default, Vue.js components are loaded on the dashboard. 

### Creating Tables with <ns-crud/>

The CRUD table is a UI feature that helps to display data in a table that can be sorted, filtered and used (edit, delete, bulk-actions, export, import).
Behind the scene, NexoPOS will loads the configuration as it's defined on the CRUD component class. You can also jump into that to make some changes. 
For example, that class can be used : 
- to hide, show columns (see the method `getColumns`).
- to define bulk actions
- to define export operations
- to handle bulk actions (once triggered by the user)
- to define actions by row

To get started using the CRUD Table, you just need to use the following to define the Crud Table : 

```html
<ns-crud></ns-crud>
```
Obviously, this requires more parameters such as : 

#### src
Address to the resource on the backend. Here, you just need to provide the default CRUD Route + your CRUD identifier (namespace). Using the previous `ns.customers-group` as an identifier,
the final `src` will look like `url( 'api/nexopos/v4/crud/ns.customers-groups' )`. Here is how you'll use it concretly.

```blade
<ns-crud src="{{ url( 'api/nexopos/v4/crud/ns.customers-groups' ) }}"></ns-crud>
```

#### create-url
This is the placeholder link to create a new component entity. Concretly, the link you'll add here will be used on the "+" button. And this should takes the user
to the creation form. Example : 

```blade
<ns-crud 
  create-url="{{ url( 'dashboard/customers/groups/create' ) }}"
  src="{{ url( 'api/nexopos/v4/crud/ns.customers-groups' ) }}"></ns-crud>
```

#### Slots

As the table should be multilingual, translated labels should be provided as a slot. Slot should be added as sub dom elements of the `<ns-crud/>` component. You therefore need to make sure that components is not a single tag ~~`<ns-crud/>`~~ but as a paired tags `<ns-crud></ns-crud>`. Here is the list of supported slot and their definition : 


| Slot | Definition | Example |
|------| ---------- | ------- |
| bulk-label | Provides a default label for the bulk select dropdown input | `<template v-slot:bulk-label>{{ __( 'Bulk Actions' ) }}</template>` |
| error-no-action | Error used when no action is selected. | `<template v-slot:error-no-action>{{ __( 'You need to select an action.' ) }}</template>` |
| error-no-selection | Error used when no entry is selected. | `<template v-slot:error-no-selection>{{ __( 'You need to select at least one entry.' ) }}</template>` |



### Creating Forms with <ns-crud-form/>

The creating forms loads his configuration from the CRUD Component class. Before, you need to start using the `<ns-crud-form></ns-crud-form>` component. As the previous table component,
this components also requires parameters and slots.

#### return-url
This should be a link that takes the user back to the components

```blade
<ns-crud-form return-url="{{ url( '/dashboard/customers/groups' ) }}"></ns-crud-form>
```
#### submit-url
This should be the URL to the backend where the form is validated and saved. Usually, you'll use the default CRUD url + your component identifier (namespace). Using the same example we're working on (customers groups), the submit url will look like this.

```blade
<ns-crud-form submit-url="{{ url( '/api/nexopos/v4/crud/ns.customers-groups' ) }}"></ns-crud-form>
```

#### src
This is a URL that helps the CRUD forms to configure the fields and tabs. Once again you'll use the default CRUD url + your component identifier + `/form-config`
Here is an example

```blade
<ns-crud-form src="{{ url( '/api/nexopos/v4/crud/ns.customers-groups/form-config' ) }}"></ns-crud-form>
```

#### Slots

As the form should be multilingual, translated labels should be provided as a slot. Slot should be added as sub dom elements of the `<ns-crud-form/>` component. You therefore need to make sure that components is not a single tag ~~`<ns-crud-form/>`~~ but as a paired tags `<ns-crud-form></ns-crud-form>`. Here is the list of supported slot and their definition : 


| Slot | Definition | Example |
|------| ---------- | ------- |
| title | Is the title of the form | `<template v-slot:title>{{ __( 'Create Customer Group' ) }}</template>` |
| save | Used as label of the save button | `<template v-slot:title>{{ __( 'Save Group' ) }}</template>` |
| error-required | Is used when an input throw a required error during the validation | `<template v-slot:required>{{ __( 'This field is required' ) }}</template>` |
| error-invalid-form | Is the error used when the form is not valid | `<template v-slot:error-invalid-form>{{ __( 'Unable to save the group. The form is not valid.' ) }}</template>` |

## Advanced Crud Configuration
A CRUD class that is generated comes with a bunch of options that can be changed to match a specific needs. We'll here points the possibles modifications
on the crud instance.

### Model Global & Local Scopes
As a CRUD class is attached to a model, all model events are then fired while creating, editing and deleting. You can also apply [global and local scopes](https://laravel.com/docs/8.x/eloquent#query-scopes) to your model and it will apply to the CRUD class. For example, if you have a list of users where there are Administrators and Simple users, you might want to create a crud interface that only displays Administrators, in that case, you'll to create a custom class that extends the user class and provide either a global or a local scope to it.

```php
class SimpleUser extends User
{
    public function booted()
    {
        static::addGlobalScopes( 'role_id', function( Builder $builder ) {
            $role   = Role::namespace( 'user' )->first();
            $builder->where( 'role_id', $role->id );
        });
    }
}
```

Then while linking the model to your CRUD instance, you just have to use the `SimpleUser::class` instead of the user class.

### Crud Relationships
While creating your crud resource, you might want to create a link with the model used on the Crud object with table (or model). The Crud object uses a different way of defining relations (different from Angular defaults). The definition of a crud relationship is defined on the property `relations`, which contains an array of definitions. Here is an example: 

```php
// ...
public $relations   =   [
    [ 'nexopos_users as user', 'nexopos_expenses.author', '=', 'user.id' ]
]
```
Here, the definition create a link between `nexopos_users` aliased as `user` and `nexopos_expenses` where the columns `author` equal the `user` (alias) `id`. As you might have noticed it's possible to define alias.

### Tabs and Relationships
You might have a CRUD form that has general information stored on a specific model and other tabs with informations stored on different related model. We've used this approach to store customer general informations on "nexopos_customers" table and customer shipping & billing informations to store them on "nexopos_customers_addresses" (using custom CustomerAddress model with different scopes), but visually all that are displayed on the same UI.

<img src="https://user-images.githubusercontent.com/5265663/95012272-3b1e4080-062f-11eb-855e-86a5fd453bf2.jpg"/>

In order to make this possible, you need to define a `tabsRelations` on your CRUD class.

```php
protected $tabsRelations   = [
  'billing'   =>  [ CustomerBillingAddress::class, 'customer_id', 'id' ]
];
```

Here, the "billing" should match an existing tabs on the defined form (fetched using `getForms()`). That tabs identifier has as value an array which contains : 

- The related model class name
- The local key : On the related model class name, that point to the crud model. 
- The foreign key : Usually this is the "id" of the crud model.

Once defined, while editing and creating, the informations defined on the tabs that are linked to a related model will be stored and updated separately from the Crud model.

### Coloring Crud Rows
Sometime, you might want to give a different colors to a row that is rended on the table. You're not able to inject CSS classes that will overwrite the default css class that applies to each row. You only have to set a "$cssClass" property to the row available as "$entry" on the method `setActions`.

```php
  // instance of crud
  public function setActions( $entry, $namespace )
  {
      // will only add a class if the status of the $entry is "paid".
      if ( $entry->status === 'paid' ) {
          $entry->{ '$cssClass' }   = 'bg-green-100 border-green-200 border text-sm';
      }

      return $entry;
  }
```

You might need to understand how TailwindCSS works to apply [color utilities classes](https://tailwindcss.com/docs/customizing-colors#default-color-palette).

### How To Open A Popup While Clickin On Row Actions
Usually row actions takes you to a different page where specific actions can be taken. However, if you would like to display informations on a popup on the same page where a row action is clicked, you need to proceed using the "popup" actions. This will show you how to turn an action into a popup.

![screenshot-nexopos-v4 std-2020 10 17-14_32_09](https://user-images.githubusercontent.com/5265663/96338398-b64b1200-1085-11eb-9782-3bbe7939d989.jpg)

By Action we mean the actions that are defined using "setActions" method of a crud class. Here is an example of defined actions : 

```php
  // ...
  public function setActions( $entry, $namespace )
  {
      // you can make changes here
      $entry->{'$actions'}    =   [
          [
              'label'     =>  __( 'Delete' ),
              'namespace' =>  'delete',
              'type'      =>  'DELETE',
              'url'       =>  url( '/api/nexopos/v4/crud/ns.orders/' . $entry->id ),
              'confirm'   =>  [
                  'message'  =>  __( 'Would you like to delete this ?' ),
              ]
          ]
      ];

      return $entry;
  }
  // ...
```
In order to create a popup action you need to set the type to "DELETE" : 

```php
  // ...
  public function setActions( $entry, $namespace )
  {
      // you can make changes here
      $entry->{'$actions'}    =   [
          [
              'label'     =>  __( 'My Popup' ),
              'namespace' =>  'my-popup',
              'type'      =>  'POPUP',
          ], [
              'label'     =>  __( 'Delete' ),
              'namespace' =>  'delete',
              'type'      =>  'DELETE',
              'url'       =>  url( '/api/nexopos/v4/crud/ns.orders/' . $entry->id ),
              'confirm'   =>  [
                  'message'  =>  __( 'Would you like to delete this ?' ),
              ]
          ]
      ];

      return $entry;
  }
  // ...
```
Once the button is clicked, it will trigger an event available using the javascript object `nsEvent` :

```js
nsEvent.emit({
    identifier: 'ns-table-row-action',
    value: { action, row: this.row, component: this }
});
```

Where identifier should help you identify the event is about clicking on an action and value which as the folowing properties : 
- action : that is one action as it's defined on `setActions`, example : 

```php
[
  'label'     =>  __( 'My Popup' ),
  'namespace' =>  'my-popup',
  'type'      =>  'POPUP'
]
```

- row : that is the line from where the action is clicked.
- component : is the Vue Component that represent the row which options is clicked.

So, in order to be able to catch that even, you need to register a javascript file that is available on the footer of the CRUD resource you're targetting.
Ideally, you'll add a filter using the hook "ns-crud-footer" : 

```php
use App\Classes\Hook;
use App\Classes\Response;

// ...

Hook::addFilter( 'ns-crud-footer', function( Response $response, string $identifier ) {
    if ( $identifier === 'ns.products' ) { // here 'ns.products' is the identifier of the Crud resource so that this output is only added on that resource.
        $response->addOutput( ( string ) view( '/path/to/your/view' ) );
    }
}, 10, 2 );
```

Then, you view should register the script (using script tag). On your script, you'll just have to listen
to the event before opening the popup.

```js
nsEvent.subject().subscribe( event => {
    if ( event.identifier === 'ns-table-row-action' && event.value.action.namespace === 'my-popup' ) {
        Popup.show( YourPopupComponentVue, {} );
    }
}) 
```
