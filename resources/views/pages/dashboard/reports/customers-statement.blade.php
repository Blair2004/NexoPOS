@extends( 'layout.dashboard' )

@section( 'layout.dashboard.with-title' )
    <ns-customers-statement-report
        store-name="{{ ns()->option->get( 'ns_store_name' ) }}" 
        store-logo="{{ ns()->option->get( 'ns_store_rectangle_logo' ) }}"
        search-url="{{ ns()->route( 'ns-api.customers.search' ) }}"
        ></ns-customers-statement-report>
@endsection