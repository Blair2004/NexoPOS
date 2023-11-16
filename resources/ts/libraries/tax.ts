import * as math from 'mathjs';
export default class Tax {
    static compute( type, value, rate ) {
        switch ( type ) {
            case 'inclusive':
                return Tax.computeInclusive( value, rate );
            case 'exclusive':
                return Tax.computeExclusive( value, rate )
        }
    }

    static computeInclusive( value, rate ) {
        return math.chain( 
            math.chain( value ).divide(
                math.chain( rate ).add( 100 ).done()
            ).done() 
        ).multiply( 100 ).done();
    }

    static computeExclusive( value, rate ) {
        return math.chain(value).divide(100).multiply( math.chain(rate).add(100).done() ).done();
    }

    static getTaxValue( type, value, rate ) {
        switch( type ) {
            case 'inclusive':
                return value - <number>Tax.compute( type, value, rate );
            case 'exclusive':
                return <number>Tax.compute( type, value, rate ) - value;
        }

        return 0;
    }
}