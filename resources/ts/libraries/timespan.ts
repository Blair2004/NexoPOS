import { __ } from "./lang";
import countdown from "~/libraries/countdown";
import moment from "moment";

countdown.setFormat({
    singular: ` ${__( 'millisecond| second| minute| hour| day| week| month| year| decade| century| millennium' )}`,
    plural: ` ${__( 'milliseconds| seconds| minutes| hours| days| weeks| months| years| decades| centuries| millennia' )}`,
    last: ` ${__( 'and' )} `,
    delim: ', ',
    empty: ''
});

export const timespan = function( date ) {
    const from  =   moment( ns.date.current, 'YYYY-MM-DD HH:mm:ss' );
    const now   =   moment( date );
    const comparison    =   from.isBefore( now ) ? 'after' : 'before';

    const diffInMonths      =   Math.abs( from.diff( now, 'months' ) ) > 0;
    const diffInDays        =   Math.abs( from.diff( now, 'days' ) ) > 0;
    const diffInHours       =   Math.abs( from.diff( now, 'hours' ) ) > 0;
    const diffInMinutes     =   Math.abs( from.diff( now, 'minutes' ) ) > 0;
    const diffInSeconds     =   Math.abs( from.diff( now, 'seconds' ) ) > 0;

    let unit;

    if ( diffInMonths ) {
        unit    =   countdown.MONTHS;
    } else if ( diffInDays ) {
        unit    =   countdown.DAYS;
    } else if ( diffInHours ) {
        unit    =   countdown.HOURS;
    } else if ( diffInMinutes ) {
        unit    =   countdown.MINUTES;
    } else if ( diffInSeconds ) {
        unit    =   countdown.SECONDS;
    } else {
        unit    =   countdown.MONTHS|countdown.DAYS|countdown.HOURS|countdown.MINUTES;
    }

    const diff  =   countdown( from.toDate(), now.toDate(), unit, undefined, undefined );

    return ( comparison === 'before' ? __( '{date} ago' ) : __( 'In {date}' ) ).replace( '{date}', diff.toString() );
}