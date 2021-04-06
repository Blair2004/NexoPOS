import moment from "moment";

declare const ns;

/**
 * till will make sure the frontend
 * time remain in sync or almost with
 * the backend date
 */
ns.date.moment          =   moment( ns.date.current );

/**
 * define the interval that will
 * increate the value of the date
 * locally
 * @param {Interval} interval
 */
ns.date.interval        =   setInterval( () => {
    ns.date.moment.add( 1, 'seconds' );
    ns.date.current     =   moment( ns.date.current )
        .add( 1, 'seconds' )
        .format( 'YYYY-MM-DD HH:mm:ss' );
}, 1000 );