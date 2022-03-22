<?php
use App\Models\User;
?>
@extends( 'layout.base' )

@section( 'layout.base.header' )
    @parent
    @yield( 'layout.base.header.pos' )
@endsection

@section( 'layout.base.body' )
<div id="pos-app" class="h-full w-full relative">
    <div id="loader" class="top-0 anim-duration-500 fade-in-entrance left-0 absolute w-full h-full flex flex-col items-center justify-center">
        @if ( ns()->option->get( 'ns_store_square_logo', false ) )
        <img src="{{ ns()->option->get( 'ns_store_square_logo' ) }}" alt="POS">
        @else
        <img src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" class="w-32" alt="POS">
        @endif
        <p class="font-semibold py-2">{{ __( 'Loading...' ) }}</p>
    </div>
    <ns-pos></ns-pos>
</div>
@endsection

@section( 'layout.base.footer' )
    @parent
    <script src="{{ asset( ns()->isProduction() ? 'js/pos-init.min.js' : 'js/pos-init.js' ) }}"></script>
    <script>
    POS.defineTypes(<?php echo json_encode( $orderTypes );?>);
    POS.defineOptions( <?php echo json_encode( $options );?>);
    POS.defineSettings({
        barcode_search          :   true,
        text_search             :   false,
        edit_purchase_price     :   <?php echo User::allowedTo( 'nexopos.pos.edit-purchase-price' ) ? 'true' : 'false';?>,
        edit_settings           :   <?php echo User::allowedTo( 'nexopos.pos.edit-settings' ) ? 'true' : 'false';?>,
        products_discount       :   <?php echo User::allowedTo( 'nexopos.pos.products-discount' ) ? 'true' : 'false';?>,
        cart_discount           :   <?php echo User::allowedTo( 'nexopos.pos.cart-discount' ) ? 'true' : 'false';?>,
        breadcrumb              :   [],
        products_queue          :   [],
        pos_items_merge         :   <?php echo ns()->option->get( 'pos_items_merge', 'no' ) === 'yes' ? 'true' : 'false';?>,
        unit_price_editable     :   <?php echo ns()->option->get( 'ns_pos_unit_price_ediable', 'yes' ) === 'yes' ? 'true' : 'false';?>,
        urls                    :   <?php echo json_encode( $urls );?>
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
    <script src="{{ asset( ns()->isProduction() ? 'js/pos.min.js' : 'js/pos.js' ) }}"></script>
@endsection