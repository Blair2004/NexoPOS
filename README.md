# NexoPOS 4
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