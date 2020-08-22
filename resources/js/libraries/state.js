import { BehaviorSubject } from "rxjs";

export class State {
    /**
     * 
     * @param {BehaviorSubject} behaviorState 
     */
    constructor( state ) {
        this.behaviorState   =  new BehaviorSubject;
        this.behaviorState.subscribe( state => {
            this.state  =   state;
            console.log( state );
        });

        this.setState( state );
    }

    setState(object) {
        this.behaviorState.next({ ...this.state, object });
    }
}