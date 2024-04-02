@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex flex-col flex-auto">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="px-4 flex-auto flex flex-col" id="dashboard-content">
        @include( 'common.dashboard.title' )
        <ns-crud-form 
            return-url="{{ url( '/dashboard/customers' ) }}"
            submit-url="{{ url( '/api/crud/ns.customers' ) }}"
            src="{{ url( '/api/crud/ns.customers/form-config' ) }}">
            <template v-slot:title>Customer Name</template>
            <template v-slot:save>Save Customer</template>
        </ns-crud-form>
    </div>
</div>
@endsection