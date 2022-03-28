<?php
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div>
    @include( Hook::filter( 'ns-dashboard-header', '../common/dashboard-header' ) )
    <div class="px-4 flex flex-col" id="dashboard-content">
        <div class="flex-auto flex flex-col">
        @include( 'common.dashboard.title' )
        </div>
        <div>
            <ns-settings
                url="{{ ns()->url( '/api/nexopos/v4/settings/' . $identifier ) }}"
                
                >
                <template v-slot:error-form-invalid>{{ __( 'Unable to proceed the form is not valid.' ) }}</template>
            </ns-settings>
        </div>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
{!! ( string ) Hook::filter( 'ns-settings-footer', new Output, $identifier ) !!}
@endsection