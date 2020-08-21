import { sample } from "lodash";
import * as rx from 'rx';

export class SnackBar {
    // window.snackbarQueue will be used to stack the snackbars
    constructor() {
        if ( window.snackbarQueue === undefined ) {
            window.snackbarQueue    =   [];
            this.queue              =   window.snackbarQueue;
        }
    }

    show( message, label, options = { duration: 3000, type : 'info' }) {
        return rx.Observable.create( observer => {
            const { buttonNode, textNode, snackWrapper, sampleSnack }        =   this.__createSnack({ message, label, type : options.type });

            buttonNode.addEventListener( 'click', ( event ) => {
                observer.onNext( buttonNode );
                observer.onCompleted();
                sampleSnack.remove();
            });

            this.__startTimer( options.duration, sampleSnack );
        });
    }

    error( message, label, options = { duration: 3000, type : 'error' }) {
        return this.show( message, label, {...options, ...{ type : 'error' } } );
    }

    success( message, label, options = { duration: 3000, type : 'success' }) {
        return this.show( message, label, {...options, ...{ type : 'success' } } );
    }

    info( message, label, options = { duration: 3000, type : 'info' }) {
        return this.show( message, label, {...options, ...{ type : 'info' } } );
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
                    wrapper.remove();
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

    __createSnack({ message, label, type = 'info' }) {
        const snackWrapper          =   document.getElementById( 'snack-wrapper' ) || document.createElement( 'div' );
        const sampleSnack           =   document.createElement( 'div' );
        const textNode              =   document.createElement( 'p' );
        const buttonsWrapper        =   document.createElement( 'div' );
        const buttonNode            =   document.createElement( 'button' );
        
        let buttonThemeClass        =   '';
        let snackThemeClass         =   '';

        switch( type ) {
            case 'info': 
                buttonThemeClass    =   'text-white hover:bg-blue-400 bg-blue-500';
                snackThemeClass     =   'bg-gray-900 text-white';
            break;
            case 'error': 
                buttonThemeClass    =   'text-red-700 hover:bg-white bg-white';
                snackThemeClass     =   'bg-red-500 text-white';
            break;
            case 'success': 
                buttonThemeClass    =   'text-green-700 hover:bg-white bg-white';
                snackThemeClass     =   'bg-green-500 text-white';
            break;
        }
        
        textNode.textContent        =   message;

        /**
         * if there is not label
         * on the button, it's useless to add it
         */
        if ( label ) {
            buttonNode.textContent      =   label;
            buttonNode.setAttribute( 'class', `px-3 py-2 shadow rounded-lg font-bold ${buttonThemeClass}` );
            buttonsWrapper.appendChild( buttonNode );
        }

        sampleSnack.appendChild( textNode );
        sampleSnack.appendChild( buttonsWrapper );
        sampleSnack.setAttribute( 'class', `rounded-lg py-2 px-3 w-3/4 my-2 shadow-lg flex justify-between items-center ${snackThemeClass}` );

        snackWrapper.setAttribute( 'id', 'snack-wrapper' );
        snackWrapper.setAttribute( 'class', 'absolute bottom-0 w-full flex justify-between items-center flex-col')
        snackWrapper.appendChild( sampleSnack );

        document.body.appendChild( snackWrapper );

        return { snackWrapper, sampleSnack, buttonsWrapper, buttonNode, textNode };
    }
}