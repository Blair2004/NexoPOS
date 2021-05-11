declare const ns;
declare const nsHttpClient;
declare const RxJS;
class NsLanguage {
    private languages   =   {};

    constructor() {
        this.boot();
    }

    async boot() {
        return await this.loadJson();
    }

    loadJson() {
        return new Promise( ( resolve, reject ) => {
            
            const that          =   this;
            
            for( let i = 0 ; i < ns.langFiles.length ; i++ ) {
                const xhttp                 =   new XMLHttpRequest();
                xhttp.onreadystatechange    =   function() {
                    if (this.readyState == 4 && this.status == 200) {
                        const result   =   JSON.parse( xhttp.responseText );
                        for( let key in result ) {
                            that.languages[ key ]   =   result[ key ];
                        }
                    }
                };
                xhttp.open("GET", ns.langFiles[i], true);
                xhttp.send();
            }
        });
    }

    getEntries() {
        return this.languages;
    }
}

( window as any ).nsLanguage         =   new NsLanguage;
