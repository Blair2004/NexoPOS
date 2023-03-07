import { Subject } from "rxjs";
import { createApp } from "vue/dist/vue.esm-bundler";
import popupInjector from "./popup-injector";

declare const document;
declare const window;

export class Popup {
    private config  =   {
        primarySelector     :   undefined,
        popupClass  :   'shadow-lg h-half w-1/2 bg-white',
    };

    private container       =   document.createElement( 'div' );
    private popupBody       =   document.createElement( 'div' );
    private popupSelector   =   '';
    private event: Subject<{ event: string, value: any }>;
    private instance: any;
    private parentWrapper: HTMLDivElement | HTMLBodyElement;

    constructor( config: {
        primarySelector?: string,
        popupClass?: string
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

        this.event              =   new Subject;
    }

    static show( component, params = {}, config = {}) {
        const popup     =   new Popup( config );
        popup.open( component, params );
        return popup;
    }

    private hash() {
        let text    = "";
        let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for (let i = 0; i < 10; i++)
            text += possible.charAt(Math.floor(Math.random() * possible.length));

        return text;
    }

    async open( component, params = {} ) {
        if ( typeof component === 'function' ) {
            try {
                component = (await component()).default;
            } catch( exception ) {
                /**
                 * it has failed, maybe it's an inline-component.
                 * In that situation, we don't need to resolve the default.
                 */
            }
        }

        const body  =   document.querySelector( 'body' ).querySelectorAll( 'div' )[0];
        this.parentWrapper.style.filter     =   'blur(4px)';
        body.style.filter                   =   'blur(6px)';

        this.container.setAttribute( 'class', 'absolute top-0 left-0 w-full h-full flex items-center justify-center is-popup' );

        /**
         * We need to listen to click even on the container
         * as it might be used to close the popup
         */
        this.container.addEventListener( 'click', ( event ) => {
            /**
             * This means we've strictly clicked on the container
             */
            if ( Object.values( event.target.classList ).includes( 'is-popup' ) ) {
                /**
                 * this will emit an even
                 * when the overlay is clicked
                 */
                this.event.next({
                    event: 'click-overlay',
                    value: true
                });

                event.stopPropagation();
            }
        });

        /**
         * We don't want to propagate to the
         * overlay, that closes the popup
         */
        this.popupBody.addEventListener( 'click', ( event ) => {
            event.stopImmediatePropagation();
        });

        const actualLength      =   document.querySelectorAll( '.is-popup' ).length;

        this.container.id                   =   'popup-container-' + this.hash();
        this.popupSelector                  =   `#${this.container.id}`;

        this.popupBody.setAttribute( 'class', 'zoom-out-entrance popup-body' );
        this.popupBody.setAttribute( 'data-index', actualLength );
        this.popupBody.innerHTML            =   '<ns-popup-component></ns-popup-component>';
        this.container.appendChild( this.popupBody );

        document.body.appendChild( this.container );

        /**
         * We'll provide a reference of the
         * wrapper so that the component
         * can manipulate that.
         */
        this.instance        =   createApp({});

        this.instance.use( popupInjector, { params, $popup : this, $popupParams: params });

        /**
         * Registering the custom components
         */
        for( let name in window.nsComponents ) {
            this.instance.component( name, window.nsComponents[ name ] );
        }

        this.instance.component( 'ns-popup-component', component );

        /**
         * Mounting the final app.
         */
        this.instance.mount( `#${this.container.id}` );
    }

    close(immediately: boolean = false) {
        /**
         * The Subject we've initialized earlier
         * need to be closed
         */
        this.event.unsubscribe();

        /**
         * For some reason we need to fetch the
         * primary selector once again.
         */
        this.parentWrapper.style.filter   =   'blur(0px)';
        const body  =   document.querySelector( 'body' ).querySelectorAll( 'div' )[0];

        if ( document.querySelectorAll( '.is-popup' ).length <= 1 ) {
            body.style.filter   =   'blur(0px)';
        }

        const selector          =   `${this.popupSelector} .popup-body`;

        this.popupBody          =   document.querySelector( selector );
        this.popupBody.classList.remove( 'zoom-out-entrance' );
        this.popupBody.classList.add( 'zoom-in-exit' );

        this.container          =   document.querySelector( `${this.popupSelector}` );
        this.container.classList.remove( 'is-popup' );

        /**
         * Let's start by destorying the
         * Vue component attached to the popup
         */

        if (immediately) {
            this.instance.unmount();
            this.container.remove();
        } else {
            setTimeout( () => {
                this.instance.unmount();
                this.container.remove();
            }, 250 ); // as by default the animation is set to 500ms
        }
    }
}
