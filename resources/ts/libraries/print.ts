declare const nsHooks;
declare const nsSnackBar;
declare const __;

export default class Print {
    private urls;
    private options;
    private printingURL     =   {
        'refund'    :   'refund_printing_url',
        'sale'      :   'sale_printing_url',
        'payment'   :   'payment_printing_url',
        'z-report'  :   'z_report_printing_url',
    }

    constructor({ urls, options }) {
        this.urls       =   urls;
        this.options    =   options;
    }

    setCustomPrintingUrl( documentType, url ) {
        this.printingURL[ documentType ] = url;
    }

    processRegularPrinting( reference_id, documentType ) {
        const item  =   document.querySelector( '#printing-section' );

        if ( item ) {
            item.remove();
        }

        console.log({ documentType })

        const url               =   this.urls[ this.printingURL[ documentType ] ].replace( '{reference_id}', reference_id );
        const printSection      =   document.createElement( 'iframe' );

        printSection.id         =   'printing-section';
        printSection.className  =   'hidden';
        printSection.src        =   url; // should be different regarding the document

        document.body.appendChild( printSection );
        
        setTimeout( () => {
            document.querySelector( '#printing-section' ).remove();
        }, 5000 );
    }

    process( reference_id, document, mode = 'aloud' ) {
        switch( this.options.ns_pos_printing_gateway ) {
            case 'default' : this.processRegularPrinting( reference_id, document ); break;
            default: this.processCustomPrinting( reference_id, this.options.ns_pos_printing_gateway, document, mode ); break;
        }
    }

    processCustomPrinting( reference_id, gateway, document, mode = 'aloud' ) {
        const params    =   { printed: false, reference_id, gateway, document, mode };
        const result =  nsHooks.applyFilters( 'ns-custom-print', {
            params,
            promise: () => new Promise( ( resolve, reject ) => {
                reject({
                    status: 'error',
                    message: __( `The selected print gateway doesn't support this type of printing.`, 'NsPrintAdapter' )
                });
            }),
        });

        result.promise().then( result => {
            nsSnackBar.success( result.message ).subscribe();
        }).catch( exception => {
            nsSnackBar.error( exception.message || __( `An error unexpected occured while printing.` ) ).subscribe();
        })    
    }
}