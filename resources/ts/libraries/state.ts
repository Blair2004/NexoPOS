import { BehaviorSubject } from "rxjs";

export class State {
    /**
     * 
     * @param {BehaviorSubject} behaviorState 
     */
    behaviorState: BehaviorSubject<{}>;

    stateStore: {}   =   {};

    constructor( state ) {
        this.behaviorState   =  new BehaviorSubject({});
        this.behaviorState.subscribe( state => {
            this.stateStore  =   state;
        });

        this.setState( state );
    }

    setState(object) {
        this.behaviorState.next({ ...this.stateStore, ...object });
    }

    get state() {
        return this.behaviorState;
    }

    subscribe( callback ) {
        this.behaviorState.subscribe( callback );
    }
}