@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div id="dashboard-content" class="h-full flex flex-col">
    @include( '../common/dashboard-header' )
    <div class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ $title ?? __( 'Unamed Page' ) }}</h3>
            <p class="text-gray-600">{{ $description ?? __( 'No Description Provided' ) }}</p>
        </div>
        <ns-rewards-system class="mt-4"
            return-link="{{ $returnLink }}"
            submit-method="{{ $submitMethod ?? 'POST' }}"
            submit-url="{{ $submitUrl }}"
            disable-tabs="true"
            src="{{ $srcUrl }}"
            :rules="{{ $reward->rules ?? [] }}"
            >
            <template v-slot:title>{{ $mainFieldLabel ?? __( 'Reward System Name' ) }}</template>
            <template v-slot:save>{{ $saveButton ?? __( 'Save' ) }}</template>
            <template v-slot:error-required>{{ $fieldRequired ?? __( 'This field is required' ) }}</template>
            <template v-slot:error-invalid-form>{{ $formNotValid ?? __( 'The form is not valid. Please check it and try again' ) }}</template>
        </ns-rewards-system>
    </div>
</div>
@endsection