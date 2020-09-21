@extends( 'layout.base' )

@section( 'layout.base.header' )
    @parent
    <script>
    const POS               =   new Object;
    POS.order               =   new Object;
    POS.order.products      =   [];
    POS.order.customer      =   new Object;
    POS.breadcrumb          =   [];
    POS.grid                =   [];
    POS.header              =   new Object;
    POS.header.buttons      =   [];
    POS.activeCategory      =   new Object;
    </script>
    @yield( 'layout.base.header.pos' )
@endsection

@section( 'layout.base.body' )
<div id="pos-app" class="h-full w-full">
    <ns-pos></ns-pos>
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/pos.js' ) }}"></script>
@verbatim
@endverbatim
@endsection