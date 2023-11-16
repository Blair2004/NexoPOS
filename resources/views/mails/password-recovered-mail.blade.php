@component('mail::message')
# {{ __( 'Password Recovered' ) }}

{{ 
    sprintf(
        __( 'Your password has been successfully updated on __%s__. You can now login with your new password.' ),
        ns()->option->get( 'ns_store_name' )
    )
}}

@component('mail::button', ['url' => route( 'ns.login' ) ])
Login
@endcomponent

<small>{{ __( 'If you haven\'t asked this, please get in touch with the administrators.' ) }}</small>

@endcomponent
