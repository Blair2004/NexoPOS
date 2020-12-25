import moment from "moment";

declare global {
    interface Window {
        ns:any;
    }
}

/**
 * till will make sure the frontend
 * time remain in sync or almost with
 * the backend date
 */
window.ns.date.moment          =   moment( window.ns.date.current );

/**
 * define the interval that will
 * increate the value of the date
 * locally
 * @param {Interval} interval
 */
window.ns.date.interval        =   setInterval( () => {
    window.ns.date.moment.add( 1, 'seconds' );
}, 1000 );