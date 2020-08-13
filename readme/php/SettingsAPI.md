#   Settings API
The settings API allows you to create ready to use form to save quickly settings on the `nexopos_options` table. 
This guide define how you can register settings and use them.

## Prior Consideration Regarding Menu
The settings API doesn't create settings menu on the dashboard sidebar. This should be made by the end developper using the [Menu API](/readme/php/MenuAPI.md).

## Initialize A Settings Page
A Settings page must be initialized as a class that inherite from the `App\Services\SettingsPage` class. This is a concrete example on how it's registered.

```php
App\Settings;

use App\Services\SettingsPage;
use App\Services\Options;

class MySettings extends SettingsPage
{
    public function __construct()
    {
        // required to populate the form.
        $options        =   app()->make( Options::class );
        
        $this->form     =   [
            'tabs'      =>  [
                'general'   =>  [
                    'label' =>  __( 'General Settings' ),
                    'fields'    =>  [
                        [
                            'label' =>  __( 'Store Name' ),
                            'name'  =>  'ns_store_name',
                            'value' =>  $options->get( 'ns_store_name' ),
                            'description'   =>  __( 'Provide the store name here.' ),
                            'validation'    =>  'required'
                        ]
                    ]
                ]
            ]
        ]
    }    
}
```

The class has a protected property named `form` which is requried as this hold the form definition. The settings page consist of tabs that has fields. 
A settings page can have 1 or multple settings page (up to 8). Each fields but be prefixed with a unique expression. Every NexoPOS fields are prefixed with `ns_`.

Regarding the field validation, you can use [aravel Validation rules](https://laravel.com/docs/7.x/validation), and during the submission, the data will be validated.

**Note:** During the submission, the data submitted to the server consist of a multidimensionnal object. Every fields are contained within tabs identifier. 
In our above example, here is the JSON object submitted to the server.

```json
{
    "general": {
        "ns_store_name": ""
    }
}
```

The following JSON is generated using the JavaScript FormValidation class.

## Register A Settings Page
Every settings page must be registered to be properly initialized. By default, all registration are made on the `App\Providers\SettingsPageProvider`, on the boot `method`.
