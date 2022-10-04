<div id="dashboard-content" class="px-4">
    <div class="flex items-center justify-center">
        <div class="md:w-1/3 w-full flex flex-col items-center justify-center">
            <i class="las la-sad-cry text-6xl font-bold"></i>
            <h2 class="font-bold text-3xl">{{ __( 'Something went wrong' ) }}</h2>
            <p class="text-center py-2">{{ __( 'The current logged user has more that 2 roles that has dashboard defined. In case multiple roles are assigned to a user, only one of these roles should have a dashboard defined.' ) }}</p>
            <div class="flex flex-col md:flex-row md:-mx-2">
                <div class="px-2">
                    <ns-link href="https://my.nexopos.com/en/documentation/troubleshooting/conflicting-dashboard" target="_blank" type="info">{{ __( 'Learn More' ) }}</ns-link>
                </div>
            </div>
        </div>
    </div>
</div>