import { ReplaySubject } from 'rxjs';

export class EventEmitter {
    _subject: ReplaySubject<any>
    
    constructor() {
        this._subject    =   new ReplaySubject;        
    }

    subject() {
        return this._subject;
    }

    emit({ identifier, value }) {
        this._subject.next({ identifier, value });
    }
}