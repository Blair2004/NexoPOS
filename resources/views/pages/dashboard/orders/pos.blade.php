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
    <script src="{{ asset( 'js/pos-init.js' ) }}"></script>
    <script>
    POS.defineTypes( @json( $orderTypes ) );
    POS.defineOptions( @json( $options ) );
    POS.defineSettings({
        barcode_search      :   true,
        text_search         :   false,
        breadcrumb          :   [],
        products_queue      :   [],
        urls                :   @json( $urls )
    });

    POS.definedPaymentsType( @json( $paymentTypes ) );
    </script>
    <script src="{{ asset( 'js/pos.js' ) }}"></script>
    <script>
    document.addEventListener( 'DOMContentLoaded', () => {
        POS.reset();
    });
    </script>
@endsection