<?php
use App\Classes\Hook;
use App\Classes\Output;
?>
@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col flex-auto">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <ns-crud-form 
            return-url="{{ $returnUrl }}"
            submit-method="{{ $submitMethod ?? 'POST' }}"
            :option-attributes='@json( $optionAttributes ?? [] )'
            :query-params='@json( $queryParams ?? [] )'
            submit-url="{{ $submitUrl }}"
            src="{{ $src }}">
            <template v-slot:title>{{ $mainFieldLabel ?? __( 'mainFieldLabel not defined' ) }}</template>
            <template v-slot:save>{{ $saveButton ?? __( 'Save' ) }}</template>
            <template v-slot:error-required>{{ $fieldRequired ?? __( 'This field is required' ) }}</template>
            <template v-slot:error-invalid>{{ $formNotValid ?? __( 'The form is not valid. Please check it and try again' ) }}</template>
        </ns-crud-form>
    </div>
</div>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
    <?php
    $output     =   new Output;
    Hook::action( 'ns-crud-form-footer', $output, $namespace )
    ?>
    {!! ( string ) $output !!}
@endsection