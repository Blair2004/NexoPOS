const rx            =   require( 'rx' );

class HttpClient {
    constructor() {
        this._subject    =   new rx.Subject; 
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
        this._subject.onNext({ identifier: 'async.start', url, data });
        return new rx.Observable.create( observer => {
            this._client[ type ]( url, data ).then( result => {
                observer.onNext( result );
                observer.onCompleted();
                this._subject.onNext({ identifier: 'async.stop' });
            }).catch( error => {
                observer.onError( error );
                this._subject.onNext({ identifier: 'async.stop' });
            });
        });
    }

    subject() {
        return this._subject;
    }

    emit({ identifier, value }) {
        this._subject.onNext({ identifier, value });
    }
}

const client    =   new HttpClient;
client.post( 'https://google.cm', { foo: 'bar' });

module.exports  =   HttpClient;