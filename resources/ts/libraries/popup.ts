import { Subject } from "rxjs";

declare const Vue;
declare const document;

export class Popup {
    private config  =   {
        primarySelector     :   undefined,
        popupClass  :   'shadow-lg h-half w-1/2 bg-white',
    }; 

    private container   =   document.createElement( 'div' );
    private popupBody   =   document.createElement( 'div' );
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
             * this will emit an even
             * when the overlay is clicked
             */
            this.event.next({
                event: 'click-overlay',
                value: true
            });

            event.stopPropagation();
        });

        /**
         * We don't want to propagate to the
         * overlay, that closes the popup
         */
        this.popupBody.addEventListener( 'click', ( event ) => {
            event.stopImmediatePropagation();
        });

        this.container.id                   =   'popup-container-' + document.querySelectorAll( '.is-popup' ).length;
        this.popupBody.setAttribute( 'class', ' zoom-out-entrance' );
        this.popupBody.innerHTML            =   '<div class="popup-body"></div>';
        this.container.appendChild( this.popupBody );  

        document.body.appendChild( this.container );

        /**
         * We'll provide a reference of the
         * wrapper so that the component
         * can manipulate that.
         */
        const componentClass        =   Vue.extend( component );
        this.instance               =   new componentClass({
            propsData:  {
                popup   :   this, // @deprecated
            }
        });

        /**
         * Let's intanciate the component
         * and mount it
         */
        this.instance.template          =   component?.options?.template || undefined;
        this.instance.render            =   component.render || undefined;
        this.instance.methods           =   component?.options?.methods || component?.methods;
        this.instance.data              =   component?.options?.data || component?.data;
        this.instance.$popup            =   this;
        this.instance.$popupParams      =   params;
        this.instance.$mount( `#${this.container.id} .popup-body` );
    }

    close() {
        /**
         * Let's start by destorying the
         * Vue component attached to the popup
         */
        this.instance.$destroy();
        
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
        
        this.popupBody.classList.remove( 'zoom-out-entrance' );
        this.popupBody.classList.add( 'zoom-in-exit' );
        this.container.classList.remove( 'is-popup' );

        setTimeout( () => {
            this.container.remove();
        }, 300 ); // as by default the animation is set to 500ms
    }
}