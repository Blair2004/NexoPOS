declare const nsHooks;
declare const nsSnackBar;
declare const __;

export default class Print {
    private settings;
    private options;
    private type;

    private printingURL     =   {
        'refund'    :   'refund_printing_url',
        'sale'      :   'sale_printing_url',
        'payment'   :   'payment_printing_url',
    }

    constructor({ settings, options, type }) {
        this.settings   =   settings;
        this.options    =   options;
        this.type       =   type || 'refund';
    }

    processRegularPrinting( reference_id ) {
        const item  =   document.querySelector( 'printing-section' );

        if ( item ) {
            item.remove();
        }

        const url               =   this.settings[ this.printingURL[ this.type ] ].replace( '{reference_id}', reference_id );
        const printSection      =   document.createElement( 'iframe' );

        printSection.id         =   'printing-section';
        printSection.className  =   'hidden';
        printSection.src        =   url;

        document.body.appendChild( printSection );
        
        setTimeout( () => {
            document.querySelector( '#printing-section' ).remove();
        }, 5000 );
    }

    printOrder( reference_id ) {
        switch( this.options.ns_pos_printing_gateway ) {
            case 'default' : this.processRegularPrinting( reference_id ); break;
            default: this.processCustomPrinting( reference_id, this.options.ns_pos_printing_gateway ); break;
        }
    }

    processCustomPrinting( reference_id, gateway ) {
        const result =  nsHooks.applyFilters( 'ns-order-custom-refund-print', { printed: false, reference_id, gateway });
        
        if ( ! result.printed ) {
            nsSnackBar.error( __( `Unsupported print gateway.` ) ).subscribe();
        }
    }
}