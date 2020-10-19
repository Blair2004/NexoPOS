export class Tax {
    static compute( type, value, rate ) {
        switch ( type ) {
            case 'inclusive':
                return Tax.computeInclusive( value, rate );
            break;
            case 'exclusive':
                return Tax.computeExclusive( value, rate )
            break;
        }
    }

    static computeInclusive( value, rate ) {
        return ( value / ( rate + 100 ) ) * 100;
    }

    static computeExclusive( value, rate ) {
        return ( value / 100 ) * ( rate + 100 );
    }

    static getTaxValue( type, value, rate ) {
        switch( type ) {
            case 'inclusive':
                return value - Tax.compute( type, value, rate );
            case 'exclusive':
                return Tax.compute( type, value, rate ) - value;
        }

        return 0;
    }
}