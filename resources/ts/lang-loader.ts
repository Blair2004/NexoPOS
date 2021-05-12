declare const ns;
declare const nsHttpClient;
declare const RxJS;
class NsLanguage {
    private languages   =   {};
    private scripts     =   [];
    private callbacks   =   [];

    constructor() {
        this.loadJson();
    }

    loadJson() {
        const promises  =   [];
        
        for( let namespace in ns.langFiles ) {
            promises.push( new Promise( ( resolve, reject ) => {
                const xhttp                 =   new XMLHttpRequest();
                xhttp.onreadystatechange    =   ( e ) => {
                    if ( (<XMLHttpRequest>e.target).readyState == 4 && (<XMLHttpRequest>e.target).status == 200) {
                        const result   =   JSON.parse( xhttp.responseText );
                        
                        for( let key in result ) {
                            if ( this.languages[ namespace ] === undefined ) {
                                this.languages[ namespace ]     =   new Object;
                            }

                            this.languages[ namespace ][ key ]   =   result[ key ]
                        }
                        resolve( this.languages );
                    }
                };
                xhttp.open("GET", ns.langFiles[namespace], true);
                xhttp.send();
            }));
        }

        Promise.all( promises ).then( () => {
            this.loadReadyScripts();
            this.loadReadyCallbacks();
        });
    }

    loadReadyScripts() {
        for( let i = 0; i < this.scripts.length ; i++ ) {
            // get some kind of XMLHttpRequest
            const xhrObj = new XMLHttpRequest();
            // open and send a synchronous request
            xhrObj.open('GET', this.scripts[i], false);
            xhrObj.send('');
            // add the returned content to a newly created script tag
            const se = document.createElement('script');
            se.type = "text/javascript";
            se.text = xhrObj.responseText;
            document.body.appendChild(se);
        }
    }

    loadReadyCallbacks() {
        this.callbacks.forEach( callback => callback() );
    }

    onReadyCallback( callback ) {
        this.callbacks.push( callback );
    }

    onReadyScript( script ) {
        if ( script.length !== undefined ) {
            this.scripts.push( ...script );
        } else {
            this.scripts.push( script );
        }
    }

    getEntries( namespace ) {
        return this.languages[ namespace ] || false;
    }
}

( window as any ).nsLanguage         =   new NsLanguage;
