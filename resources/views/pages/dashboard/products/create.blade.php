@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <ns-manage-products
            return-url="{{ $returnUrl ?? '#' }}"
            submit-method="{{ $submitMethod ?? 'POST' }}"
            submit-url="{{ $submitUrl }}"
            units-url="{{ $unitsUrl }}"
            src="{{ $src }}">
            <template v-slot:title>{{ $mainFieldLabel ?? __( 'mainFieldLabel not defined' ) }}</template>
            <template v-slot:save>{{ $saveButton ?? __( 'Save' ) }}</template>
            <template v-slot:error-required>{{ $fieldRequired ?? __( 'This field is required' ) }}</template>
            <template v-slot:error-invalid-form>{{ $formNotValid ?? __( 'The form is not valid. Please check it and try again' ) }}</template>
        </ns-manage-products>
    </div>
</div>
@endsection