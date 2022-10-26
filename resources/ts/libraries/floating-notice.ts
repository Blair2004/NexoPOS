    import { Observable } from "rxjs";

    declare const window:any;

    /**
     * define a single notice action.
     */
    export interface FloatingNoticeSingleAction {
        label: string
        onClick?: ( instance: FloatingAction ) => void
    }

    export interface FloatingNoticeOptions { duration?: (number|boolean), type?: string, actions?: { [key:string] : FloatingNoticeSingleAction } };

    export class FloatingAction {
        constructor( private instance: Element ) {
            // ...
        }

        close() {
            this.instance.classList.add( 'fade-out-exit' );
            this.instance.classList.add( 'anim-duration-300' );
            this.instance.classList.remove( 'zoom-in-entrance' );
            setTimeout( () => {
                this.instance.remove();
            }, 250 );
        }
    }

    export class FloatingNotice {
        queue;

        constructor() {
            if ( window.floatingNotices === undefined ) {
                window.floatingNotices    =   [];
                this.queue              =   window.floatingNotices;
            }
        }

        show( title, description, options: FloatingNoticeOptions = { duration: 3000, type : 'info' }) {
            const { floatingNotice }        =   this.__createSnack({ title, description, options });

            /**
             * We can't allow a notice that is permanently visible
             * without any button actions.
             */
            if ( options.actions === undefined ) {
                options.duration    =   3000;
            }

            this.__startTimer( options.duration, floatingNotice );
        }

        error( title, description, options: FloatingNoticeOptions = { duration: 3000, type : 'error' }) {
            return this.show( title, description, { ...options, ...{ type : 'error' } } );
        }

        success( title, description, options: FloatingNoticeOptions = { duration: 3000, type : 'success' }) {
            return this.show( title, description, {...options, ...{ type : 'success' } } );
        }

        info( title, description, options: FloatingNoticeOptions = { duration: 3000, type : 'info' }) {
            return this.show( title, description, {...options, ...{ type : 'info' } } );
        }

        warning( title, description, options: FloatingNoticeOptions = { duration: 3000, type : 'warning' }) {
            return this.show( title, description, {...options, ...{ type : 'warning' } } );
        }

        /**
         * 
         * @param {number} duration 
         * @param {HTMLDivElement} wrapper 
         */
        __startTimer( duration, wrapper ) {
            let timeout;
            const __startTimeOut    =   () => {
                if ( duration > 0 && duration !== false ) {
                    timeout    =   setTimeout( () => {
                        ( new FloatingAction( wrapper ) ).close();
                    }, duration );
                }
            };

            wrapper.addEventListener( 'mouseenter', () => {
                clearTimeout( timeout );
            });

            wrapper.addEventListener( 'mouseleave', () => {
                __startTimeOut();
            });

            __startTimeOut();
        }

        __createSnack({ title, description, options }) {
            let buttonThemeClass    =   '';
            let noticeThemeClass    =   '';

            switch( options.type ) {
                case 'info': 
                    buttonThemeClass    =   '';
                    noticeThemeClass     =   'info';
                break;
                case 'error': 
                    buttonThemeClass    =   '';
                    noticeThemeClass     =   'error';
                break;
                case 'success': 
                    buttonThemeClass    =   '';
                    noticeThemeClass     =   'success';
                break;
                case 'warning': 
                    buttonThemeClass    =   '';
                    noticeThemeClass     =   'warning';
                break;
            }

            
            if ( document.getElementById( 'floating-notice-wrapper' ) === null ) {
                const parsed    =   (new DOMParser).parseFromString(`
                <div id="floating-notice-wrapper" class="absolute bottom-0 right-0 flex justify-between items-end p-2 flex-col">
                
                </div>
                `, 'text/html' );

                document.body.appendChild( parsed.querySelector( '#floating-notice-wrapper' ) );
            }

            
            const snackWrapper      =   document.getElementById( 'floating-notice-wrapper' ) || document.createElement( 'div' );

            let floatingNotice   =   ( new DOMParser ).parseFromString(`
            <div class="ns-floating-notice shadow-lg zoom-in-entrance anim-duration-300 p-2 border-t-4 mt-4 md:w-96 flex flex-col ${noticeThemeClass}">
                <div class="ns-floating-notice-content">
                    <h2 class="font-bold text-xl">${title}</h2>
                    <p>${description}</p>
                </div>
                <div class="flex justify-end w-full buttons-wrapper mt-2">
                    <!-- the button will be added here -->
                </div>
            </div>
            `, 'text/html' ).querySelector( '.ns-floating-notice' );
            

            if ( options.actions !== undefined && Object.values( options.actions ).length > 0 ) {
                for( let key in options.actions ) {
                    const buttonsWrapper        =   floatingNotice.querySelector( '.buttons-wrapper' );
                    const buttonDom             =   ( new DOMParser ).parseFromString( `
                    <div class="ns-button default ml-2">
                        <button class="px-2 py-1 shadow rounded uppercase ${buttonThemeClass}">${options.actions[ key ].label}</button>
                    </div>
                    `, 'text/html' ).firstElementChild;

                    
                    /**
                     * if the button has a defined onClick method
                     * we'll bind a click event to it, otherwiste it will close the popup
                     */
                     if ( options.actions[ key ].onClick ) {
                        buttonDom
                            .querySelector( '.ns-button' )
                            .addEventListener( 'click', () => {
                                options.actions[ key ].onClick( new FloatingAction( floatingNotice ) );
                            });
                    } else {
                        buttonDom
                            .querySelector( '.ns-button' )
                            .addEventListener( 'click', () => ( new FloatingAction( floatingNotice ) ).close() );
                    }

                    buttonsWrapper.appendChild(
                        buttonDom.querySelector( '.ns-button' )
                    );
                }
            }
            
            snackWrapper.appendChild( floatingNotice );

            return { floatingNotice };
        }
    }