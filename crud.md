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

## Using the Vue.js Components
You'll need to use Vue.js components for the table and the forms. You should note that, you're not forced to use those as you can create your own components. By default, 
Vue.js components are loaded on the dashboard. 

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

#### create-link
This is the placeholder link to create a new component entity. Concretly, the link you'll add here will be used on the "+" button. And this should takes the user
to the creation form. Example : 

```blade
<ns-crud 
  create-link="{{ url( 'dashboard/customers/groups/create' ) }}"
  src="{{ url( 'api/nexopos/v4/crud/ns.customers-groups' ) }}"></ns-crud>
```

#### Slots

As the table should be multilingual, translated labels should be provided as a slot. Here is the list of supported slot and their definition : 


| Slot | Definition | Example |
|------| ---------- | ------- |
| bulk-label | Provides a default label for the bulk select dropdown input | `<template v-slot:bulk-label>Bulk Actions</template>`


### Creating Forms with <ns-crud-form/>

The creating forms loads his configuration from the CRUD Component class. Before, you need to start using the `<ns-crud-form></ns-crud-form>` component. As the previous table component,
this components also requires parameters and slots.

