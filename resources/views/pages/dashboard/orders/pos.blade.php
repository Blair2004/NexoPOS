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

    /**
     * Keeps the products
     */
    POS.order.products      =   new RxJS.Subject([]);

    /**
     * Keeps the order types
     * can be extended via a hook.
     */
    POS.order.types         =   new RxJS.BehaviorSubject( @json( $orderTypes ) );

    /**
     * Keeps the selected customer
     */
    POS.order.customer      =   new Object;

    /**
     * Has the current POS breadcrumb.
     */
    POS.breadcrumb          =   new RxJS.BehaviorSubject([]);

    /**
     * Keeps the POS settings
     */
    POS.options             =   new RxJS.Subject({
        barcode_search      :   true,
        text_search         :   false,
        breadcrumb          :   [],
        products_queue      :   []
    });

    POS.header              =   new Object;

    /**
     * This should be used to add new component 
     * on the header of the POS.
     */
    POS.header.buttons      =   [];

    POS.settings                    =   new Object;

    POS.settings.addToCartQueue     =   new Array;
    </script>
    <script src="{{ asset( 'js/pos-init.js' ) }}"></script>
    <script src="{{ asset( 'js/pos.js' ) }}"></script>
@verbatim
@endverbatim
@endsection