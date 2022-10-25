import { Observable } from "rxjs";

declare const window:any;

/**
 * define a single notice action.
 */
export interface FloatingNoticeSingleAction {
    label: string
    onClick: () => void
}

export interface FloatingNoticeOptions { duration: (number|boolean), type?: string, actions?: { [key:string] : FloatingNoticeSingleAction } };

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
        const { sampleSnack }        =   this.__createSnack({ title, description, options });
        console.log( options );
        this.__startTimer( options.duration, sampleSnack );
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
        const snackWrapper          =   document.getElementById( 'floating-wrapper' ) || document.createElement( 'div' );
        const sampleSnack           =   document.createElement( 'div' );
        const textContainer         =   document.createElement( 'div' );
        const titleNode              =   document.createElement( 'h2' );
        const buttonsWrapper        =   document.createElement( 'div' );     
        const descriptionNode       =   document.createElement( 'p' );   
        
        let buttonThemeClass        =   '';
        let snackThemeClass         =   '';

        switch( options.type ) {
            case 'info': 
                buttonThemeClass    =   '';
                snackThemeClass     =   'info';
            break;
            case 'error': 
                buttonThemeClass    =   '';
                snackThemeClass     =   'error';
            break;
            case 'success': 
                buttonThemeClass    =   '';
                snackThemeClass     =   'success';
            break;
        }

        textContainer.setAttribute( 'class', 'ns-floating-notice-content' );
        
        titleNode.textContent        =   title;
        descriptionNode.textContent     =   description;

        /**
         * if there is not label
         * on the button, it's useless to add it
         */
        buttonsWrapper.setAttribute( 'class', `flex w-full justify-end` )

        if ( Object.values( options.actions ).length > 0 ) {
            for( let key in options.actions ) {
                const buttonWrapper         =   document.createElement( 'div' );
                buttonWrapper.setAttribute( 'class', 'ns-button default ml-2' );

                const buttonNode            =   document.createElement( 'button' );
                buttonNode.textContent      =   options.actions[ key ].label;
                buttonNode.setAttribute( 'class', `px-3 py-2 shadow rounded uppercase ${buttonThemeClass}` );
                
                /**
                 * if the button has a defined onClick method
                 * we'll bind a click event to it, otherwiste it will close the popup
                 */
                if ( options.actions[ key ].onClick ) {
                    buttonNode.addEventListener( 'click', () => options.actions[ key ].onClick( new FloatingAction( sampleSnack ) ) );
                } else {
                    buttonNode.addEventListener( 'click', () => ( new FloatingAction( sampleSnack ) ).close() );
                }

                buttonWrapper.appendChild( buttonNode );
                buttonsWrapper.appendChild( buttonWrapper );
            }
        }

        /**
         * Adding text node
         */
        textContainer.appendChild( titleNode );
        textContainer.appendChild( descriptionNode );

        sampleSnack.appendChild( textContainer );
        sampleSnack.appendChild( buttonsWrapper );
        sampleSnack.setAttribute( 'class', `zoom-in-entrance anim-duration-300 ns-floating-notice ${snackThemeClass}` );
        
        snackWrapper.appendChild( sampleSnack );

        if ( document.getElementById( 'floating-wrapper' ) === null ) {
            snackWrapper.setAttribute( 'id', 'floating-wrapper' );
            snackWrapper.setAttribute( 'class', 'absolute bottom-0 w-full flex justify-between items-end p-2 flex-col')

            document.body.appendChild( snackWrapper );
        }

        return { snackWrapper, sampleSnack, buttonsWrapper, textNode: titleNode };
    }
}