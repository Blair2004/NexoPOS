@extends( 'layout.base' )
@section( 'layout.base.body' )
<style>
    @page {
        size: A4;
        margin: 10mm;
    }
</style>
    @include( Hook::filter( 'ns-web-invoice-template', 'pages.dashboard.orders.templates._invoice', $order ) )
@endsection
