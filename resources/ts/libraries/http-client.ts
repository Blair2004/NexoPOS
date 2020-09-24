import * as rx from "rx";
import * as rxjs from 'rxjs';

export class HttpClient {
    _subject: rxjs.Subject<{}>;
    _client;

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

    _request( type, url, data = {}, config = {} ) {
        this._subject.next({ identifier: 'async.start', url, data });
        return new rxjs.Observable( observer => {
            this._client[ type ]( url, data, { 
                ...this._client.defaults[ type ],
                config
            }).then( result => {
                observer.next( result.data );
                observer.complete();
                this._subject.next({ identifier: 'async.stop' });
            }).catch( error => {
                observer.error( error.response.data );
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