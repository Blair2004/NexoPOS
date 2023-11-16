<ns-login
        :show-recovery-link="{{ ns()->option->get( 'ns_recovery_enabled' ) === 'yes' ? 'true' : 'false' }}"
        :show-register-button="{{ ns()->option->get( 'ns_registration_enabled' ) === 'no' ? 'false' : 'true' }}"
>
    <div class="w-full flex items-center justify-center">
        <h3 class="font-hairline text-sm ns-normal-text">{{ __( 'Loading...' ) }}</h3>
    </div>
</ns-login>
