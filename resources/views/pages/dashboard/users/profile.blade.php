<?php
use App\Classes\Hook;
use App\Classes\Output;
?>
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
                url="{{ $src ?? '#' }}"
                submit-url="{{ $submitUrl }}"
                >
                <template v-slot:error-form-invalid>{{ __( 'Unable to proceed the form is not valid.' ) }}</template>
            </ns-settings>
        </div>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
    {!! ( string ) Hook::filter( 'ns-profile-footer', new Output ) !!}
@endsection