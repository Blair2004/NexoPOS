import { Subject } from "rxjs";
import { shallowRef } from "vue";

declare const document;
declare const nsState;
declare const nsHotPress;

export class Popup {
    private config  =   {
        primarySelector     :   undefined,
        popupClass  :   'shadow-lg h-half w-1/2 bg-white',
    }; 

    private container       =   document.createElement( 'div' );
    private popupBody       =   document.createElement( 'div' );
    private parentWrapper: HTMLDivElement | HTMLBodyElement;

    constructor( config: {
        primarySelector?: string,
        popupClass?: string,
        closeOnOverlayClick?: boolean,
    } = {} ) {
        this.config             =   Object.assign( this.config, config );
        
        if ( 
            this.config.primarySelector === undefined &&
            document.querySelectorAll( '.is-popup' ).length > 0
        ) {
            const items             =   document.querySelectorAll( '.is-popup' ).length;
            this.parentWrapper      =   <HTMLDivElement>(document.querySelectorAll( '.is-popup' )[ items - 1 ]);
        } else {
            this.parentWrapper      =   <HTMLDivElement>document.querySelector( 'body' ).querySelectorAll( 'div' )[0];
        }
    }

    static show( component, params = {}, config = {}) {
        const popup     =   new Popup( config );
        return popup.open( component, params, config );
    }

    private hash() {
        let text    = "";
        let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for (let i = 0; i < 10; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }            

        return text.toLocaleLowerCase();
    }

    open( component, params = {}, config = {} ) {
        this.popupBody       =   document.createElement( 'div' );

        if ( typeof component === 'function' ) {
            try {
                component = (async() => (await component()).default)();
            } catch( exception ) {
                /**
                 * it has failed, maybe it's an inline-component.
                 * In that situation, we don't need to resolve the default.
                 */
            }
        } else if ( typeof component.__asyncLoader === 'function' ) {
            /**
             * With this, we'll resolve the component
             * to ensure props can be added to it on runtime.
             */
            component = (async () => await component.__asyncLoader())();
        }

        const body                          =   document.querySelector( 'body' ).querySelectorAll( 'div' )[0];
        this.parentWrapper.style.filter     =   'blur(4px)';
        body.style.filter                   =   'blur(6px)';
        
        let popups              =   [];
        const currentState      =   <{ popups: {}[]}>nsState.state.getValue();

        if ( currentState.popups !== undefined ) {
            popups  =   currentState.popups;
        }
        
        /**
         * We'll add the new popups
         * to the popups stack.
         */
        let props   =   {};
        
        if ( component.props ) {
            props     =   Object.keys( params ).filter( param => component.props.includes( param ) ).reduce( ( props, param ) => {
                props[ param ]  =   params[ param ];
                return props;
            }, {});
        }
        
        const popup     =   {
            hash: `popup-${this.hash()}-${this.hash()}`,
            component: shallowRef( component ),
            close: ( callback = null ) => this.close( popup, callback ),
            props,
            params,
            config,
        };

        popups.push( popup );

        nsState.setState({ popups });

        return popup;
    }

    close( popup, callback = null ) {
        /**
         * For some reason we need to fetch the 
         * primary selector once again.
         */
        this.parentWrapper.style.filter   =   'blur(0px)';
        const body  =   document.querySelector( 'body' ).querySelectorAll( 'div' )[0];

        if ( document.querySelectorAll( '.is-popup' ).length <= 1 ) {
            body.style.filter   =   'blur(0px)';
        }

        const selector          =   `#${popup.hash} .popup-body`;
        const popupBody          =   document.querySelector( selector );
        popupBody.classList.remove( 'zoom-out-entrance' );
        popupBody.classList.add( 'zoom-in-exit' );

        const container          =   document.querySelector( `#${popup.hash}` );
        container.classList.remove( 'is-popup' );

        setTimeout( () => {
            const { popups }    =   nsState.state.getValue();
            const index         =   popups.indexOf( popup );
            popups.splice( index, 1 );
            nsState.setState({ popups });

            /**
             * this will destroy the listener to avoid
             * the event bound to still trigger the callback.
             */
            nsHotPress.destroy( `popup-esc-${popup.hash}` );

            if ( callback !== null ) {
                return callback( popup );
            }
        }, 250 ); // because the remove animation last 250ms
    }
}