@component('mail::message')
@if ( ns()->option->get( 'ns_notifications_registrations_user_activate_body' ) )
    {!! ns()->option->get( 'ns_notifications_registrations_user_activate_body' ) !!}
@else
    # {{ __( 'Activate Your Account' ) }}

    {{ 
        sprintf(
            __( 'The account you have created for "%s", require an activation. In order to proceed, please click on the following link' ),
            ns()->option->get( 'ns_store_name' ),
            $user->username
        )
    }}
@endif

@component('mail::button', ['url' => route( 'ns.activate-account', [
    'user'  =>  $user->id,
    'token' =>  $user->activation_token
]) ])
{{ __( 'Activate Your Account' ) }}
@endcomponent

@endcomponent