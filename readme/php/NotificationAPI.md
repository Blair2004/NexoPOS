# Getting Started

The notifications are helpful to display relevant messages to a user or group of user on a specific matter. It shouldn't be used for a discussion messages but can be used
to share a status on a process, an information about the store details or a user account. NexoPOS 4.x has not a NotificationsService that ease creating notifications
for users or group of users.

## Different Between Notifications To User and Group of User

As said before notification can be used to display a status on a process. Usually such notification are made to a specific group of users that need to be aware of that process.
For example, when products come to expiricy, staff in charge of the stock management should be aware of that. In other hand, if an account changes users permissions, 
this specific might be notified of the change.

## Using the NotificationService

The `NotificationsService` doesn't have any dependency, therefore it can be imported and initialized using the class  `App\Services\NotificationService` or `app()->make( ... )`.
Once initialized, the object has access to various methods such as : 

### Dispatching new notification 

*create*

```php
$notificationService  =   new App\Services\NotificationService;
$notification         =   $notificationService->create([
  'title'             =>  'A new order awaiting for shipping',
  'message'           =>  'The order XZY is awaiting for processing and shipping.',
  'dismissable'       =>  true,
  'source'            =>  'system', // or module namespace
  'url'               =>  '#', // url here
  'identifier'        =>  'unique-name', // can be unique to avoid emitting new notifications on a same topic.
]);
```

Once a notification has been created, it can be dispatched for : 

*User Group*

```php
$notification->dispatchForGroup( Role::namespace( 'admin' ) );
```

*Collection of Users*

```php
$users  = User::whereIn( 'id', [ 10,20,300]);
$notification->dispatchForUsers( $users );
```

*Specific User*

```php
$notification->dispatchForUser( User::find(22) );
```

### Delete Notification Having Specific Identifier

If for any reason a specific process has been completed for example, you might want to remove all notifications emitted about that. You'll then use `deleteHavingIdentifier` to 
clear all notification issued with the provided identifier (no matter the users or the groups).

```php
$notificationService  =   new App\Services\NotificationService;
$notificationService->deleteHavingIdentifier( 'ns-expired-products' );
```

You can also delete for a specific user

```php
$notificationService  =   new App\Services\NotificationService;
$notificationService->deleteNotificationsFor( User::find(22) );
```
or delete notification by their ID

```php
$notificationService  =   new App\Services\NotificationService;
$notificationService->deleteSingleNotification( 12 );
```


