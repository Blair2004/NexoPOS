@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
            <div class="page-inner-header mb-4">
                <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
                <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
            </div>
        </div>
        <div>
            <ns-settings
                url="{{ ns()->url( '/api/nexopos/v4/settings/ns.accounting' ) }}"
                
                >
                <template v-slot:error-form-invalid>{{ __( 'Unable to proceed the form is not valid.' ) }}</template>
            </ns-settings>
        </div>
    </div>
</div>
@endsection