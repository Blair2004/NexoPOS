import { BehaviorSubject, Observable, Subject } from 'rxjs';
import { filter, map, distinctUntilChanged } from 'rxjs/operators';

type PropertyChange<T, K extends keyof T = keyof T> = {
    key: K;
    previous: T[K];
    current: T[K];
};

export class ReactiveObject<T extends Record<string, any>> {
    private readonly stateSubject: BehaviorSubject<T>;
    private readonly changesSubject = new Subject<PropertyChange<T>>();

    public readonly state$: Observable<T>;

    constructor(initialState: T) {
        this.stateSubject = new BehaviorSubject<T>(initialState);
        this.state$ = this.stateSubject.asObservable();
    }

    get value(): T {
        return this.stateSubject.value;
    }

    subscribe(callback: (state: T) => void) {
        return this.state$.subscribe(callback);
    }

    changes(): Observable<PropertyChange<T>> {
        return this.changesSubject.asObservable();
    }

    property<K extends keyof T>(key: K): Observable<T[K]> {
        return this.state$.pipe(
            map(state => state[key]),
            distinctUntilChanged()
        );
    }

    propertyChanges<K extends keyof T>(key: K): Observable<PropertyChange<T, K>> {
        return this.changesSubject.pipe(
            filter(change => change.key === key)
        ) as Observable<PropertyChange<T, K>>;
    }

    set<K extends keyof T>(key: K, value: T[K]): void {
        this.update({
            [key]: value,
        } as Partial<T>);
    }

    update(patch: Partial<T>): void {
        const previousState = this.stateSubject.value;

        const nextState = {
            ...previousState,
            ...patch,
        };

        const changes: PropertyChange<T>[] = [];

        for (const key of Object.keys(patch) as Array<keyof T>) {
            const previousValue = previousState[key];
            const currentValue = nextState[key];

            // top-level comparison only
            if (previousValue !== currentValue) {
                changes.push({
                    key,
                    previous: previousValue,
                    current: currentValue,
                });
            }
        }

        if (changes.length === 0) {
            return;
        }

        this.stateSubject.next(nextState);

        for (const change of changes) {
            this.changesSubject.next(change);
        }
    }
}