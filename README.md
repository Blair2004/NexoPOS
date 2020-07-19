# NexoPOS 4

## Installation
Before showing the step to install the application there is prior consideration to have in mind. The current root folder of the application, having the folders "app", "bootstrap", "config"... shouldn't be at the root of your server. If you're using Linux, you should configure apache to use the folder "public" (where the index.php is located) as the RootDocument of the installation. For Windows users, with [laragon](https://laragon.org/), you can also point what is the root directory. This technique prevents a lot of exploits. 

The following installation steps require additionnal skills on using CLI (Command Line Interface), but when we'll release NexoPOS builds, that will be a full installation with all the dependencies. We might also create an installer with a very simplified user interface.

- Make sure to have PHP 7.4 & Apache Configured with required extensions : php-xml, php-mbstring, php-msqli... These are often already provided by virtual server like Laragon, XAMP, WAMP, MAMP.
- [Installing Composer](https://getcomposer.org/download/).
- Install Git (that will be helfpul if you want to contribue or just to download).
- Run the following CLI command on the directory where NexoPOS should be installed : `sudo git clone https://github.com/blair2004/NexoPOS-v4.git`
- Run on the CLI `cd NexoPOS-v4`, if that's the directory name created by the previous step.
- Run on the CLI `composer install`, to install Laravel and all dependencies.
- (Optional) if the project comes without a .env file, you need to create one. You can use the .env.example that should be available at the root. Then run `php artisan key:generate`
- (Optional) Run on the CLI `npm i` to install JavaScript dependencies if you plan to contribute.
- (Optional) Run `php artisan serve` if you don't have your virtual server pointing to your installation. This will run a php server for development purpose only.

As NexoPOS doesn't have a frontend already, you'll end on the default Laravel page. Access `/do-setup/` to launch the installer.

## Contribution Guidelines
Do you plan to contribute ? That's awesome. We're open to any type of contributions. If you're a developper, you'll start by forking the project and deploying that locally for further tests. If youjust have some ideas, consider posting that as an issue. We'll review the ideas and decide to implement it.

## Menu API
The menu API is exposed by the MenuService. This class registers and displays menus on the dashboard.
Every menu should have as a key prefixed with a unique identifier. Similar keys are just merged. This could
be a good way to overwrite existing menu. Preferably, all menu should have unique identifier.

Menu Example : 

```php
$menus  =   [
    'dashboard' =>  [
        'href'          =>  url( '/dashboard' ),
        'label'         =>  __( 'Dashboard' ),
        'icon'          =>  'la-home',
        'notification'  =>  10
    ]
]
```

A menu might have childrens which are sub links. Usually, children should be used to group same menu together.

```php
$menus  =   [
    'dashboard' =>  [
        'href'          =>  url( '/dashboard' ),
        'label'         =>  __( 'Dashboard' ),
        'icon'          =>  'la-home',
        'notification'  =>  10,
        'childrens'     =>  [

        ]
    ]
]
```

Providing an `href` for the top menu is not necessary while having children. This should make sure no navigation is triggered while
clicking on a menu that has children, so that these latest are revealed to the user. A menu without `href` key has "javascript:void(0)" as replacement. 

Submenu doesn't support icones, nor notifications.

While browing to a component (for example customers), it's a good UX technique to have the customers menu expanded (toggled). If your control extends `App\Http\Controllers\DashboardController`, then it inherits the `menuService` object, used to manage the sidebar menu. You can with that aim a specific menu and toggle it like so : 

```php
use App\Http\Controllers\DashboardController;

class CustomersController extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
        $this->menuService->toggleMenu( 'customers' ); // <= here.
    }
}
```
As you already know, the menus has an identifier, and that's the identified used as unique parametter of the method `toggleMenu`.

# Javascript API
NexoPOS 4 is built on top of Vue.js. It provides bunch of components that helps to prototype UI quickly. Some part of the applications are running as SPA, such as the setup page. This section will disclose the internal JavaScript API to help understanding how it works.

## Form Validation
The Form validation helps to make sure a form is valid before submition. On NexoPOS 4.x a reactive approach is used to build a form. That ensures dynamic form creation (from remote server). The component `ns-input`, `ns-select`, `ns-radio`, `ns-datetime`, `ns-date`, `ns-textarea` are all compatible with the validation. Refer to each components to understand how the integration is made.

The Form Validation class is available on "resources/js/libraries/form-validation.js". This class expose some methods the helps interactif with a Form Array. A Form Array consist of list of fields. 

Here is how to defined a field.

### Creating An Array Of Fields
```js
const myFields    =   [
    {
        'label' =>  'Your Username',
        'name'  =>  'username',
        'description'   =>  '',
        'validation'    =>  'required',
    }
];
```

While looping the fields, the Form Validation class uses the `validation` attribute of each field as a reference for the validation. On the previous example, the rule `required` is applied. But a validation my contains more than one rule, splitted by the `|` character. 

### Init Validation Class On Fields
Ideally, it's recommended to initialize an array of field using the `createForm` method of the Form Validation class. This method ensures that the form is populated with useful properties (for the validation). This can be made this way :

```js
const myFields    =   [
    {
        'label' =>  'Your Username',
        'name'  =>  'username',
        'description'   =>  '',
        'validation'    =>  'required',
    }
];
const form      =   require( './path/to/form-validation' );
const fields    =   form.createForm( myFields );
```

### Render Forms Dynamically
As we've said that a reactive approach is used, you can use existing form components to render your form. This should be made this way.

```html
<template v-for="field of fields">
    <ns-input v-if="[ 'text', 'password' ].includes( field.type )" :field="field" @change="form.validateField( field )"></ns-input>
</template>
```

### Form Validation
Before proceeding to the validaton, it's required to make sure the provided data are valid using the ruleset defined on each field. This can be made using the `validateForm` methods which accepts an array of fields (initialized with `createForm`). Here is how you can acheive that : 

```js
// within a component
methods: {
    submitForm() {
        if ( this.form.validateForm( this.fields ) ) {
            // means the form is valid.
        }
    }
}
// ...
```
