# Introduction
Filters is part of the Hook api of NexoPOS that helps to extends the application. 
Filters are values that can be mutated by subscribers, while actions are even that can be listened by subscribers. Here is how to hook into a filter : 
```php
use Hook
//...
  public function __construct()
  {
    Hook::addFilter( 'ns-validation', [ $this, 'changeValidationRules' ]);
  }
  
  // ...
  public function changeValidationRules( $rules )
  {
    // changes rules here
    return $rules;
  }
```

Here is the list off all available filters as long as their purpose and arguments.

| Filter                   | Description         | Arguments  | Version     | Status |
| ------------------------ | ------------------- | ---------- | ----------- | ------ |
| {namespace}-catch-action | Catch bulk action of a specific CRUD component. | `boolean`<br>`Illuminate\Http\Request`| 4.0-beta-1 | Valid |
| {namespace}-crud-actions | Adds actions on each row part of the result.data array. | `Illuminate\Database\Eloquent\Model`| 4.0-beta-1 | Valid |
| ns.crud-resource         | Used to return relevant CRUD component class when there is a match with the identifier. | `<string>namespace`<br>`<number>?identifier` | 4.0-beta-1 | Valid |
| ns-crud-form             | Used to hold form as defined on the `getForm` on the CRUD component class. | `<array>form`<br> `<string>namespace`<br> `<array>(model, namespace, id)`| 4.0-beta-1 | Valid |
| ns-validation            | Contains the validation rules for a specific CRUD Component class.      | `<array>validation`| 4.0-beta-1 | Valid |
| ns-dashboard-menus       | Contains the array where is defined the Dashboard menus.                | `<array>validation`| 4.0-beta-1 | Valid |
|ns.before-login-fields    | Add or modify output before login fields | `App\Classes\Response`   | 4.0-beta-1 | Valid    |
|ns.after-login-fields     | Add or modify output after login fields  | `App\Classes\Response`   | 4.0-beta-1 | Valid    |
|ns-crud-footer     | Add or modifier output on crud tables footer | `App\Classes\Response`   | 4.0-beta-1 | Valid    |
| ns-pos-settings-tabs | Add or modifiy POS settings | Array | 4.0-beta-1 | Valid |
| ns-login-fields | Add or modifiy login fields | Array | 4.0-beta-1 | Valid |
| ns-login-footer | inject output at the end of the sign-in page | Array | 4.0-beta-1 | Valid |
| ns-login-form | provide a way to inject code while the login form is being validated | Array | 4.0-beta-1 | Valid |
| ns-register-footer | Allow injecting output at the footer of the registration page | Array | 4.0-beta-1 | Valid |
| ns-table-prefix | provides table name | Array | 4.0-beta-1 | Valid |
| ns-route | returns the route value | Array | 4.0-beta-1 | Valid |
| ns-register-subscribers | Determine if subscriber should be begistered | Array | 4.0-beta-1 | Valid |
| ns-model-table | provides a way to overwrite model tables | Array | 4.0-beta-1 | Valid |
| ns-route-dashboard-home | used to allow dashboard home route to be overwritten | Array | 4.0-beta-1 | Valid |
| ns-dashboard-header | returns the output of the dashboard header | Array | 4.0-beta-1 | Valid |
| ns-media-path | Determine the path where uploaded images are saved | Array | 4.0-beta-1 | Valid |
| ns-route-name | Used to overwrite routes name | Array | 4.0-beta-1 | Valid |
| ns-pos-printing-url | Used to overwrite routes name | Array | 4.0-beta-1 | Valid |
| ns-delivery-status | Allow override on the procurement delivery status. | Array | 4.0-beta-1 | Valid |
| ns-reset-table | Determines if a table can be truncated or not. Returns the table name or false to prevent the action. | Array | 4.0-beta-1 | Valid |
| ns-common-routes | Execute within a middleware that covers all common routes. Common routes excludes system route (modules, users), but instead this gives a way to control the access to certain features. | Array | 4.0-beta-1 | Valid |
| ns-product-history-operation | Provides an array of possible stock operation on the product history. | Array | 4.0-beta-1 | Valid |
| ns-login-redirect | returns the default redirection url, where the user is redirected after authentication. The second parameter which is a boolean, defines wether or not the user will be redirected to an intended page. "True" if the user intended to access to a page, "False" if not. | Array | 4.0-beta-1 | Valid |

