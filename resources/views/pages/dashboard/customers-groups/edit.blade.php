@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div id="dashboard-content" class="h-full flex flex-col flex-auto">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col">
        <div class="page-inner-header mb-4">
            <h3 class="text-3xl text-gray-800 font-bold">{{ __( 'Update Group' ) }}</h3>
            <p class="text-gray-600">{{ __( 'Modify an existing customer group' ) }}</p>
        </div>
        <ns-crud-form 
            return-url="{{ url( '/dashboard/customers/groups' ) }}"
            submit-method="PUT"
            submit-url="{{ url( '/api/crud/ns.customers-groups/' . $group->id ) }}"
            src="{{ url( '/api/crud/ns.customers-groups/form-config/' . $group->id ) }}">
            <template v-slot:title>Group Name</template>
            <template v-slot:save>Update Group</template>
            <template v-slot:error-required>The following field is required</template>
            <template v-slot:error-invalid-form>The form is not valid. Please check it and try again</template>
        </ns-crud-form>
    </div>
</div>
@endsection