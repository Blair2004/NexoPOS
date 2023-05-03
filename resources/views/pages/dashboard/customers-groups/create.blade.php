@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col flex-auto">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Create Customer Group' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Save a new customer group' ) }}</p>
        </div>
        <ns-crud-form 
            return-url="{{ url( '/dashboard/customers/groups' ) }}"
            submit-url="{{ url( '/api/crud/ns.customers-groups' ) }}"
            src="{{ url( '/api/crud/ns.customers-groups/form-config' ) }}">
            <template v-slot:title>Group Name</template>
            <template v-slot:save>Save Group</template>
            <template v-slot:error-required>The following field is required</template>
            <template v-slot:error-invalid-form>The form is not valid. Please check it and try again</template>
        </ns-crud-form>
    </div>
</div>
@endsection