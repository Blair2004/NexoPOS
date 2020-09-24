import { BehaviorSubject } from "rxjs";

export class State {
    /**
     * 
     * @param {BehaviorSubject} behaviorState 
     */
    behaviorState: BehaviorSubject<{}>;

    state: {}   =   {};

    constructor( state ) {
        this.behaviorState   =  new BehaviorSubject({});
        this.behaviorState.subscribe( state => {
            this.state  =   state;
        });

        this.setState( state );
    }

    setState(object) {
        this.behaviorState.next({ ...this.state, object });
    }
}