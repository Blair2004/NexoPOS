@extends( 'layout.base' )

@section( 'layout.base.header' )
    @parent
    @yield( 'layout.base.header.pos' )
@endsection

@section( 'layout.base.body' )
<div id="pos-app" class="h-full w-full">
    <ns-pos></ns-pos>
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script>
    const POS               =   new Object;
    POS.order               =   new Object;
    POS.order.products      =   new RxJS.Subject([]);
    POS.order.types         =   new RxJS.BehaviorSubject( @json( $orderTypes ) );
    POS.order.customer      =   new Object;
    POS.breadcrumb          =   [];
    POS.grid                =   [];
    POS.header              =   new Object;
    POS.header.buttons      =   [];
    POS.activeCategory      =   new Object;
    </script>
    <script src="{{ asset( 'js/pos-header.js' ) }}"></script>
    <script src="{{ asset( 'js/pos.js' ) }}"></script>
@verbatim
@endverbatim
@endsection