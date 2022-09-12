@component('mail::message')

@if ( $options->get( 'ns_notifications_registrations_administrator_email_body' ) )
    {!! $options->get( 'ns_notifications_registrations_administrator_email_body' ) !!}
@else
    # {{ __( 'New User Registration') }}

    {{ 
        sprintf(
            __( 'A new user has registered to your store (%s) with the email %s.' ),
            $options->get( 'ns_store_name' ),
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