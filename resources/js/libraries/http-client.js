import * as rx from "rx";
import * as rxjs from 'rxjs';

export class HttpClient {
    constructor() {
        this._subject    =   new rxjs.Subject; 
    }

    defineClient( client ) {
        this._client    =   client;
    }

    post( url, data ) { 
        return this._request( 'post', url, data );
    }

    get( url ) {
        return this._request( 'get', url );
    }

    delete( url ) {
        return this._request( 'delete', url );
    }

    put( url, data ) {
        return this._request( 'put', url, data );
    }

    _request( type, url, data = {} ) {
        this._subject.next({ identifier: 'async.start', url, data });
        return new rxjs.Observable( observer => {
            this._client[ type ]( url, data ).then( result => {
                observer.next( result.data );
                observer.complete();
                this._subject.next({ identifier: 'async.stop' });
            }).catch( error => {
                observer.error( error );
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