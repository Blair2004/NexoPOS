<div id="dashboard-content" class="px-4">
    <div class="flex items-center justify-center">
        <div class="md:w-1/3 w-full flex flex-col items-center justify-center">
            <i class="las la-sad-cry text-6xl font-bold"></i>
            <h2 class="font-bold text-3xl">{{ __( 'No Dashboard Assigned' ) }}</h2>
            <p class="text-center py-2">{{ __( 'All the roles assigned to the user doens\'t have any dashboard defined (or all dashboard are set to none). Consider assigning at least a dashboard to one of the role assigned to the user.' ) }}</p>
            <div class="flex flex-col md:flex-row md:-mx-2">
                <div class="px-2">
                    <ns-link href="https://my.nexopos.com/en/documentation/troubleshooting/no-dashboard" type="info">{{ __( 'Learn More' ) }}</ns-link>
                </div>
            </div>
        </div>
    </div>
</div>