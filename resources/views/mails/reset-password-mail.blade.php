@component('mail::message')
# {{ __( 'Password Recovery' ) }}

{!! 
    sprintf(
        __( 'Someone has requested to reset your password on __"%s"__. If you remember having done that request, please proceed by clicking the button below. ' ),
        ns()->option->get( 'ns_store_name' )
    )
!!}

@component('mail::button', ['url' => route( 'ns.new-password', [
    'user'  =>  $user->id,
    'token' =>  $user->activation_token
])])
{{ __( 'Reset Password' ) }}
@endcomponent

@endcomponent
