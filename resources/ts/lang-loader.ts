declare const ns;
declare const nsHttpClient;

class NsLanguage {
    private languages   =   {};

    constructor() {
        this.boot();
    }

    async boot() {
        return await this.loadJson( '/lang' );
    }

    loadJson( path ) {
        console.log( 'will load' );
        return new Promise( ( resolve, reject ) => {
            nsHttpClient.get( `${path}/${ns.language}.json` )
                .subscribe( ( result: any ) => {
                    for( let key in result ) {
                        this.languages[ key ]   =   result[ key ];
                    }
                    resolve( true );
                    console.log( 'has loaded' );
                });
        });
    }

    getEntries() {
        return this.languages;
    }
}

( window as any ).nsLanguage         =   new NsLanguage;
