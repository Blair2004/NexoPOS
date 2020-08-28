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

## Securing Menus Using Permissions

Most of the time your menus will link to a page that is secured. If that page is already using `ns()->resctrict([...])`, it's ideal to hide the menu in order to avoid the use facing the "NotEnoughPermissionException" error page. For that, you'll need to use permissions on your menu.

The principle is simple, on the main menu or sub menu, you just have to add a new key value "permissions" => [].

**Example :**
```php
$menus  =   [
    'dashboard' =>  [
        'href'          =>  url( '/dashboard' ),
        'label'         =>  __( 'Dashboard' ),
        'icon'          =>  'la-home',
        'permissions'    =>  [ 'see.dashboard' ], // assuming "see.dashboard" is a valid permission namespace.
        'notification'  =>  10,
        'childrens'     =>  [
            [
                'label'         =>  'Update',
                'permissions    =>  [ 'see.update' ],
                'href'          =>  url( '/dashboard/update' )
            ]
        ]
    ]
]
```
