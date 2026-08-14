const numericValue = ( value ) => {
    const parsedValue = Number.parseFloat( value );

    return Number.isFinite( parsedValue ) ? parsedValue : 0;
};

const sumEntries = ( entries, field ) => ( entries || [] )
    .reduce( ( total, entry ) => total + numericValue( entry[field] ), 0 );

export const normalizeWeeklyReport = ( result = [] ) => result.map( day => ({
    label: day.label,
    shortLabel: Array.from( day.label || '' ).slice( 0, 3 ).join( '' ),
    current: {
        amount: sumEntries( day.current?.entries, 'day_paid_orders' ),
        count: sumEntries( day.current?.entries, 'day_paid_orders_count' ),
    },
    previous: {
        amount: sumEntries( day.previous?.entries, 'day_paid_orders' ),
        count: sumEntries( day.previous?.entries, 'day_paid_orders_count' ),
    },
}) );

export const percentageChange = ( currentValue, previousValue ) => {
    const current = numericValue( currentValue );
    const previous = numericValue( previousValue );

    if ( previous === 0 ) {
        return current === 0 ? 0 : null;
    }

    return ( ( current - previous ) / previous ) * 100;
};

export const chartPoints = ( values, width, height, padding, maximumValue ) => {
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const interval = values.length > 1 ? plotWidth / ( values.length - 1 ) : 0;
    const safeMaximum = maximumValue > 0 ? maximumValue : 1;

    return values.map( ( value, index ) => ({
        x: padding.left + ( interval * index ),
        y: padding.top + plotHeight - ( numericValue( value ) / safeMaximum * plotHeight ),
    }) );
};

export const smoothLinePath = ( points ) => {
    if ( points.length === 0 ) {
        return '';
    }

    if ( points.length === 1 ) {
        return `M ${points[0].x} ${points[0].y}`;
    }

    let path = `M ${points[0].x} ${points[0].y}`;

    for ( let index = 1; index < points.length; index++ ) {
        const previous = points[index - 1];
        const current = points[index];
        const midpointX = ( previous.x + current.x ) / 2;
        const midpointY = ( previous.y + current.y ) / 2;

        path += ` Q ${previous.x} ${previous.y} ${midpointX} ${midpointY}`;

        if ( index === points.length - 1 ) {
            path += ` Q ${current.x} ${current.y} ${current.x} ${current.y}`;
        }
    }

    return path;
};

export const areaPath = ( points, baseline ) => {
    if ( points.length === 0 ) {
        return '';
    }

    return `${smoothLinePath( points )} L ${points.at( -1 ).x} ${baseline} L ${points[0].x} ${baseline} Z`;
};
