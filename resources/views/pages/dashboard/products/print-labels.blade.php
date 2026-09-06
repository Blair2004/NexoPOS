@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body' )
<div class="h-full flex-auto flex flex-col">
    @include( Hook::filter( 'ns-dashboard-header-file', '../common/dashboard-header' ) )
    <div class="flex-auto flex flex-col overflow-hidden" id="dashboard-content">
        <ns-print-label
            barcodeurl="{{ ns()->asset( 'storage/products/barcodes' ) }}"
            storename="{{ ns()->option->get( 'ns_store_name' ) }}">
        </ns-print-label>
    </div>
</div>
@endsection