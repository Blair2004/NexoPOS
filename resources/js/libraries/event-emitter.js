const rx    =   require( 'rx' );

class EventEmitter {
    constructor() {
        this._subject    =   new rx.Subject;        
    }

    subject() {
        return this._subject;
    }

    emit({ identifier, value }) {
        this._subject.onNext({ identifier, value });
    }
}

module.exports  =   EventEmitter;