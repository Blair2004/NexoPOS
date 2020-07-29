@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div id="dashboard-content" class="h-full flex flex-col">
    @include( '../common/dashboard-header' )
    <div class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Create Customer' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Add a new customers to the system' ) }}</p>
        </div>
        <ns-crud-form 
            submit-url="{{ url( '/api/nexopos/v4/crud/ns.customers' ) }}"
            src="{{ url( '/api/nexopos/v4/crud/ns.customers/form-config' ) }}">
            <template v-slot:title>Customer Name</template>
            <template v-slot:save>Save Customer</template>
        </ns-crud-form>
    </div>
</div>
@endsection