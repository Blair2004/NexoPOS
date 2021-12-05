declare const nsHooks;
declare const nsSnackBar;
declare const __;

export default class Print {
    private settings;
    private options;

    constructor({ settings, options }) {
        this.settings   =   settings;
        this.options    =   options;
    }

    processRegularPrinting( order_id ) {
        const item  =   document.querySelector( 'printing-section' );

        if ( item ) {
            item.remove();
        }

        const url               =   this.settings.printing_url.replace( '{order_id}', order_id );
        const printSection      =   document.createElement( 'iframe' );

        printSection.id         =   'printing-section';
        printSection.className  =   'hidden';
        printSection.src        =   url;

        document.body.appendChild( printSection );
        
        setTimeout( () => {
            document.querySelector( '#printing-section' ).remove();
        }, 5000 );
    }

    printOrder( order_id ) {
        switch( this.options.ns_pos_printing_gateway ) {
            case 'default' : this.processRegularPrinting( order_id ); break;
            default: this.processCustomPrinting( order_id, this.options.ns_pos_printing_gateway ); break;
        }
    }

    processCustomPrinting( order_id, gateway ) {
        const result =  nsHooks.applyFilters( 'ns-order-custom-refund-print', { printed: false, order_id, gateway });
        
        if ( ! result.printed ) {
            nsSnackBar.error( __( `Unsupported print gateway.` ) ).subscribe();
        }
    }
}