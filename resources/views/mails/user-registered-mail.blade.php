@component('mail::message')

@if ( ns()->option->get( 'ns_notifications_registrations_administrator_email_body' ) )
    {!! ns()->option->get( 'ns_notifications_registrations_administrator_email_body' ) !!}
@else
    # {{ __( 'New User Registration') }}

    {{ 
        sprintf(
            __( 'A new user has registered to your store (%s) with the email %s.' ),
            ns()->option->get( 'ns_store_name' ),
            $user->email
        )
    }}
@endif

@component('mail::button', [ 'url' => route( 'ns.dashboard.users.edit', [
    'user'  =>  $user->id
]) ])
{{ __( 'Profile' ) }}
@endcomponent

@endcomponent