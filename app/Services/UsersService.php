<?php

namespace App\Services;

use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Mail\ActivateYourAccountMail;
use App\Mail\UserRegisteredMail;
use App\Mail\WelcomeMail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttribute;
use App\Models\UserRoleRelation;
use App\Models\UserWidget;
use Exception;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UsersService
{
    public function __construct()
    {
        // ...
    }

    /**
     * get all user from a specific group
     *
     * @param string
     * @return array of users
     */
    public function all( $namespace = null )
    {
        if ( $namespace != null ) {
            return Role::namespace( $namespace )->users()->get();
        } else {
            return User::get();
        }
    }

    /**
     * Will either create or update an existing user
     * that will check the attribute or the user
     *
     * @param  array $attributes
     * @param  User  $user
     * @return array $response
     */
    public function setUser( $attributes, $user = null )
    {
        $validation_required = ns()->option->get( 'ns_registration_validated', 'yes' ) === 'yes' ? true : false;
        $registration_role = ns()->option->get( 'ns_registration_role', false );
        $defaultRole = Role::namespace( Role::USER )->first();
        $assignedRole = Role::find( $registration_role );
        $roleToUse = $registration_role === false ? $defaultRole : $assignedRole;

        if ( ! $defaultRole instanceof Role ) {
            throw new NotFoundException( __( 'The system role "Users" can be retrieved.' ) );
        }

        if ( ! $assignedRole instanceof Role ) {
            throw new NotFoundException( __( 'The default role that must be assigned to new users cannot be retrieved.' ) );
        }

        collect( [
            'username' => fn() => User::where( 'username', $attributes[ 'username' ] ),
            'email' => fn() => User::where( 'email', $attributes[ 'email' ] ),
        ] )->each( function ( $callback, $key ) use ( $user ) {
            $query = $callback();

            if ( $user instanceof User ) {
                $query->where( 'id', '<>', $user->id );
            }

            $user = $query->first();

            if ( $user instanceof User ) {
                throw new NotAllowedException(
                    sprintf(
                        __( 'The %s is already taken.' ),
                        $key
                    )
                );
            }
        } );

        $user = new User;
        $user->username = $attributes[ 'username' ];
        $user->email = $attributes[ 'email' ];
        $user->active = $attributes[ 'active' ] ?? ( $validation_required ? false : true );
        $user->password = Hash::make( $attributes[ 'password' ] );

        /**
         * if the validation is required, we'll create an activation token
         * and define the activation expiration for that token.
         */
        if ( $validation_required ) {
            $user->activation_token = Str::random( 20 );
            $user->activation_expiration = now()->addMinutes( config( 'nexopos.authentication.activation_token_lifetime', 30 ) );
        }

        /**
         * For additional parameters
         * we'll provide them.
         */
        foreach ( $attributes as $name => $value ) {
            if ( ! in_array(
                $name, [
                    'username',
                    'id',
                    'password',
                    'email',
                    'active',
                    'roles', // will be used elsewhere
                ]
            ) ) {
                $user->$name = $value;
            }
        }

        $user->save();

        /**
         * if the role are defined we'll use them. Otherwise, we'll use
         * the role defined by default.
         */
        $this->setUserRole( $user, $attributes[ 'roles' ] ?? ns()->option->get( 'ns_registration_role' ) );

        /**
         * Every new user comes with attributes that
         * should be explicitly defined.
         */
        $this->createAttribute( $user );

        /**
         * let's try to email the new user with
         * the details regarding his new created account.
         */
        try {
            /**
             * if the account validation is required, we'll
             * send an email to ask the user to validate his account.
             * Otherwise, we'll notify him about his new account.
             */
            if ( ! $validation_required ) {
                Mail::to( $user->email )
                    ->queue( new WelcomeMail( $user ) );
            } else {
                Mail::to( $user->email )
                    ->queue( new ActivateYourAccountMail( $user ) );
            }

            /**
             * The administrator might be aware
             * of the user having created their account.
             */
            Role::namespace( 'admin' )->users->each( function ( $admin ) use ( $user ) {
                Mail::to( $admin->email )
                    ->queue( new UserRegisteredMail( $admin, $user ) );
            } );
        } catch ( Exception $exception ) {
            Log::error( $exception->getMessage() );
        }

        $validation_required = ns()->option->get( 'ns_registration_validated', 'yes' ) === 'yes' ? true : false;
        $redirectTo = ns()->route( 'ns.login' );

        return [
            'status' => 'success',
            'message' => ! $validation_required ?
                __( 'Your Account has been successfully created.' ) :
                __( 'Your Account has been created but requires email validation.' ),
            'data' => compact( 'user', 'redirectTo' ),
        ];
    }

    /**
     * We'll define user role
     *
     * @param array $roles
     */
    public function setUserRole( User $user, $roles )
    {
        UserRoleRelation::where( 'user_id', $user->id )->delete();

        $roles = collect( $roles )->unique()->toArray();

        foreach ( $roles as $roleId ) {
            $relation = new UserRoleRelation;
            $relation->user_id = $user->id;
            $relation->role_id = $roleId;
            $relation->save();
        }
    }

    /**
     * Check if a user belongs to a group
     */
    public function is( string|array $group_name ): bool
    {
        $roles = Auth::user()
            ->roles
            ->map( fn( $role ) => $role->namespace );

        if ( is_array( $group_name ) ) {
            return $roles
                ->filter( fn( $roleNamespace ) => in_array( $roleNamespace, $group_name ) )
                ->count() > 0;
        } else {
            return in_array( $group_name, $roles->toArray() );
        }
    }

    /**
     * Clone a role assigning same permissions
     */
    public function cloneRole( Role $role, $name = null ): array
    {
        $newRole = $role->toArray();

        unset( $newRole[ 'id' ] );
        unset( $newRole[ 'created_at' ] );
        unset( $newRole[ 'updated_at' ] );

        /**
         * We would however like
         * to provide a unique name and namespace
         */
        $name = $name ?: sprintf(
            __( 'Clone of "%s"' ),
            $newRole[ 'name' ]
        );

        $namespace = Str::slug( $name );

        $newRole[ 'name' ] = $name;
        $newRole[ 'namespace' ] = $namespace;
        $newRole[ 'locked' ] = 0; // shouldn't be locked by default.

        /**
         * @var Role
         */
        $newRole = Role::create( $newRole );
        $newRole->addPermissions( $role->permissions );

        return [
            'status' => 'success',
            'message' => __( 'The role has been cloned.' ),
            'data' => [
                'role' => $newRole,
            ],
        ];
    }

    /**
     * Will create the user attribute
     * for the provided user if that doesn't
     * exist yet.
     */
    public function createAttribute( User $user ): void
    {
        if ( ! $user->attribute instanceof UserAttribute ) {
            $userAttribute = new UserAttribute;
            $userAttribute->user_id = $user->id;
            $userAttribute->language = ns()->option->get( 'ns_store_language' );
            $userAttribute->save();
        }
    }

    /**
     * Stores the widgets details
     * on the provided area
     */
    public function storeWidgetsOnAreas( array $config, ?User $user = null ): array
    {
        $userId = $user !== null ? $user->id : Auth::user()->id;

        extract( $config );
        /**
         * @var array $column
         */
        foreach ( $column[ 'widgets' ] as $position => $columnWidget ) {
            $widget = UserWidget::where( 'identifier', $columnWidget[ 'component-name' ] )
                ->where( 'column', $column[ 'name' ] )
                ->where( 'user_id', $userId )
                ->first();

            if ( ! $widget instanceof UserWidget ) {
                $widget = new UserWidget;
            }

            $widget->identifier = $columnWidget[ 'component-name' ];
            $widget->class_name = $columnWidget[ 'class-name' ];
            $widget->position = $position;
            $widget->user_id = $userId;
            $widget->column = $column[ 'name' ];
            $widget->save();
        }

        $identifiers = collect( $column[ 'widgets' ] )->map( fn( $widget ) => $widget[ 'component-name' ] )->toArray();

        UserWidget::whereNotIn( 'identifier', $identifiers )
            ->where( 'column', $column[ 'name' ] )
            ->where( 'user_id', $userId )
            ->delete();

        return [
            'status' => 'success',
            'message' => __( 'The widgets was successfully updated.' ),
        ];
    }

    /**
     * Will generate a token for either the
     * logged user or for the provided user
     */
    public function createToken( $name, ?User $user = null ): array
    {
        if ( $user === null ) {
            /**
             * @var User $user
             */
            $user = Auth::user();
        }

        return [
            'status' => 'success',
            'message' => __( 'The token was successfully created' ),
            'data' => [
                'token' => $user->createToken( $name ),
            ],
        ];
    }

    /**
     * Returns all generated token
     * using the provided user or the logged one.
     */
    public function getTokens( ?User $user = null ): EloquentCollection
    {
        if ( $user === null ) {
            /**
             * @var User $user
             */
            $user = Auth::user();
        }

        return $user->tokens()->orderBy( 'created_at', 'desc' )->get();
    }

    public function deleteToken( $tokenId, ?User $user = null )
    {
        if ( $user === null ) {
            /**
             * @var User $user
             */
            $user = Auth::user();
        }

        $user->tokens()->where( 'id', $tokenId )->delete();

        return [
            'status' => 'success',
            'message' => __( 'The token has been successfully deleted.' ),
        ];
    }

    public function checkPermission( $permission, ?User $user = null ): bool
    {
        ns()->restrict( $permission );

        return true;
    }
}
