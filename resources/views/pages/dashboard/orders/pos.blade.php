@extends( 'layout.base' )

@section( 'layout.base.header' )
    @parent
    @yield( 'layout.base.header.pos' )
@endsection

@section( 'layout.base.body' )
<div id="pos-app" class="h-full w-full relative">
    <ns-pos></ns-pos>
    <div id="loader" class="top-0 anim-duration-500 fade-in-entrance left-0 absolute w-full h-full flex flex-col items-center justify-center bg-gray-200">
        <img src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" class="w-32" alt="POS">
        <p class="font-semibold py-2 text-gray-700">{{ __( 'Loading...' ) }}</p>
    </div>
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( 'js/pos-init.js' ) }}"></script>
    <script>
    POS.defineTypes( <?php echo json_encode( $orderTypes );?>);
    POS.defineOptions( <?php echo json_encode( $options );?>);
    POS.defineSettings({
        barcode_search      :   true,
        text_search         :   false,
        breadcrumb          :   [],
        products_queue      :   [],
        urls                :   <?php echo json_encode( $urls );?>
    });

    POS.definedPaymentsType( <?php echo json_encode( $paymentTypes );?> );

    document.addEventListener( 'DOMContentLoaded', () => {
        const loader    =   document.getElementById( 'loader' );
        loader.classList.remove( 'fade-in-entrance' );
        loader.classList.add( 'fade-out-exit' );
        
        setTimeout( () => {
            loader.remove();
        }, 500 ); 
        POS.reset();
    });    
    </script>
    <script src="{{ asset( 'js/pos.js' ) }}"></script>
@endsection