import * as rx from "rx";
import * as rxjs from 'rxjs';

declare const nsHooks;

export class HttpClient {
    _subject: rxjs.Subject<{}>;
    _client;
    private _lastRequestData;

    constructor() {
        this._subject    =   new rxjs.Subject; 
    }

    defineClient( client ) {
        this._client    =   client;
    }

    post( url, data, config = {} ) { 
        return this._request( 'post', url, data, config );
    }

    get( url, config = {} ) {
        return this._request( 'get', url, undefined, config );
    }

    delete( url, config = {} ) {
        return this._request( 'delete', url, undefined, config );
    }

    put( url, data , config = {} ) {
        return this._request( 'put', url, data, config );
    }

    get response() {
        return this._lastRequestData;
    }

    _request( type, url, data = {}, config = {} ) {
        /**
         * for an unknown reason
         * trailing slash is buggy on https.
         */
        url     =   nsHooks.applyFilters( 'http-client-url', url.replace( /\/$/, '' ) );
        this._subject.next({ identifier: 'async.start', url, data });

        return new rxjs.Observable( observer => {
            this._client[ type ]( url, data, { 
                ...this._client.defaults[ type ],
                ...config
            }).then( result => {
                this._lastRequestData   =   result;
                observer.next( result.data );
                observer.complete();
                this._subject.next({ identifier: 'async.stop' });
            }).catch( error => {
                observer.error( error.response?.data || error.response || error );
                this._subject.next({ identifier: 'async.stop' });
            });
        })
    }

    subject() {
        return this._subject;
    }

    emit({ identifier, value }) {
        this._subject.next({ identifier, value });
    }
}