<ns-password-lost :show-recovery-link="{{ ns()->option->get( 'ns_recovery_enabled' ) === 'yes' ? 'true' : 'false' }}">
    <div class="w-full flex items-center justify-center">
        <h3 class="font-hairline text-sm">{{ __( 'Loading...' ) }}</h3>
    </div>
</ns-password-lost>