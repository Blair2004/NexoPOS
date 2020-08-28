@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="flex-auto flex flex-col">
    @include( '../common/dashboard-header' )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
            <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
        </div>
        <ns-manage-products
            return-url="{{ $returnUrl ?? '#' }}"
            submit-method="{{ $submitMethod ?? 'POST' }}"
            submit-url="{{ $submitUrl }}"
            src="{{ $src }}">
            <template v-slot:title>{{ $mainFieldLabel ?? __( 'mainFieldLabel not defined' ) }}</template>
            <template v-slot:save>{{ $saveButton ?? __( 'Save' ) }}</template>
            <template v-slot:error-required>{{ $fieldRequired ?? __( 'This field is required' ) }}</template>
            <template v-slot:error-invalid-form>{{ $formNotValid ?? __( 'The form is not valid. Please check it and try again' ) }}</template>
        </ns-manage-products>
    </div>
</div>
@endsection