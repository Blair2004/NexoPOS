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

        /**
         * the language for NexoPOS is
         * fetched in priority
         */
        promises.push( this.fetchLang( 'NexoPOS', ns.langFiles ) );
        
        for( let namespace in ns.langFiles ) {
            if ( namespace !== 'NexoPOS' ) {
                promises.push( this.fetchLang( namespace, ns.langFiles ) );
            }
        }

        Promise.all( promises ).then( () => {
            this.loadReadyScripts();
            this.loadReadyCallbacks();
        });
    }

    fetchLang( namespace, files ) {
        return new Promise( ( resolve, reject ) => {
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
            xhttp.open("GET", files[namespace], true);
            xhttp.send();
        });
    }

    loadReadyScripts() {
        const scripts   =   this.scripts;
        for( let i = 0; i < scripts.length ; i++ ) {
            // get some kind of XMLHttpRequest
            // const xhrObj = new XMLHttpRequest();
            // open and send a synchronous request
            // xhrObj.open('GET', scripts[i], false);
            // xhrObj.send('');
            // add the returned content to a newly created script tag
            const script = document.createElement('script');
            script.type = "text/javascript";
            script.src  = scripts[i];
            document.body.appendChild(script);
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
