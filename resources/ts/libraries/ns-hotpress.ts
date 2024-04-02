interface Config {
    visible: string[];
    hidden: string[];
    callbacks: {
        action: string | string[];
        callback: any;
    }[];
}

export class NsHotPress 
{
    private listeners                   =   new Object;

    constructor() {
        document.addEventListener( 'keydown', ( event ) => this.processEvent( event ) );
    }
    
    /**
     * Will process all events and decide
     * to execute according to the configuration provided
     * @param event 
     * @returns void
     */
    processEvent( event ) {
        for( let index in this.listeners ) {
            /**
             * @param {array} visible
             * @param {array} hidden
             * @param {array} callbacks
             */
            const config: Config   =   this.listeners[ index ].getConfig();

            if ( config.hidden.length > 0 ) {
                const hiddenPassed     =   config.hidden.filter( (element: any) => {
                    return ( element instanceof HTMLElement ) === false && document.querySelector( element ) === null;
                }).length;                
    
                if ( hiddenPassed !== config.hidden.length ) {
                    continue;
                }
            }

            if ( config.visible.length > 0 ) {
                const visiblePassed     =   config.visible.filter( (element: any ) => {
                    return ( element instanceof HTMLElement ) === true || document.querySelector( element ) !== null;
                }).length  === config.visible.length;

                if ( ! visiblePassed ) {
                    continue;
                }
            }
            
            /**
             * Looping all declared variable
             * for a specific callback.
             */
            config.callbacks.forEach( callbackConfig => {
                if ( typeof callbackConfig.action === 'string' ) {
                    this.processSingleAction({
                        action: callbackConfig.action.trim(),
                        callback: callbackConfig.callback,
                    });
                } else if ( typeof callbackConfig.action === 'object' && callbackConfig.action !== null && callbackConfig.action.length > 0 ) {
                    callbackConfig.action.forEach( action => {
                        this.processSingleAction({
                            action: action.toString().trim(),
                            callback: callbackConfig.callback,
                        });
                    });
                }
            });
        }
    }

    processSingleAction({ action, callback }) {
        const combinaisons          =   action.split( '+' );
        const combinableKeys        =   { ctrlKey: false, altKey: false, shiftKey: false };
        
        combinaisons.forEach( combinaison => {
            switch( combinaison ) {
                case 'ctrl': combinableKeys.ctrlKey = true; break;
                case 'alt': combinableKeys.altKey = true; break;
                case 'shift': combinableKeys.shiftKey = true; break;
            }
        });

        const keys                  =   combinaisons.filter( key => ! [ 'ctrl', 'alt', 'shift' ].includes( key ) );

        this.executeCallback({ event, combinableKeys, callback, key: keys[0] });
    }

    /**
     * Execute a call if that match a specific combinaison or not
     * @param {object}  event
     * @param {object}  callback
     * @param {object}  combinableKeys
     * @param {object}  key
     * @return void
     */
    executeCallback({ event, callback, combinableKeys, key }) {
        if ( event.key !== undefined && event.key.toLowerCase() === key.toLowerCase() ) {
            
            let canProceed  =   true;

            for( let index in combinableKeys ) {
                if ( event[ index ] !== combinableKeys[ index ] ) {
                    canProceed  =   false;
                }
            }

            if ( canProceed ) {
                callback( event, key );
            }
        }
    }

    /**
     * Create an instance of HotConfig.
     * @param name identifier
     * @returns HotConfig configuration
     */
    create( name ) {
        return this.listeners[ name ]      =   new HotConfig();
    }

    /**
     * Destroy an instance of HotConfig to
     * avoid further listening
     * @param name identifier
     */
    destroy( name ) {
        delete this.listeners[ name ];
    }
}

class HotConfig {
    private visible: string[]       =   [];
    private hidden: string[]        =   [];
    private callbacks               =   [];

    whenVisible( element ) {
        if ( typeof element === 'object'  ) {
            this.visible.push( ...element );
        } else {
            this.visible.push( element );
        }
        return this;
    }

    clearVisible() {
        this.visible    =   [];
        return this;
    }

    whenNotVisible( element ) {
        if ( element.length > 0 ) {
            this.hidden.push( ...element );
        } else {
            this.hidden.push( element );
        }
        return this;
    }
    
    clearHidden() {
        this.hidden     =   [];
        return this;
    }

    whenPressed( action, callback ) {
        this.callbacks.push({
            action, callback
        });
        return this;
    }

    clearCallbacks() {
        this.callbacks   =   [];
        return this;
    }

    getConfig(): Config {
        return {
            callbacks: this.callbacks,
            hidden: this.hidden,
            visible: this.visible
        };
    }
}