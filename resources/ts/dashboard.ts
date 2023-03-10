import { BehaviorSubject, forkJoin } from "rxjs";
import { map } from "rxjs/operators";
import { nsHttpClient } from "./bootstrap";

export class Dashboard {
    private _day: BehaviorSubject<{}>;
    private _bestCustomers: BehaviorSubject<[]>;
    private _bestCashiers: BehaviorSubject<[]>;
    private _weeksSummary: BehaviorSubject<{}>;
    private _recentOrders: BehaviorSubject<[]>;
    private _reports    =   {
        day:            nsHttpClient.get( '/api/nexopos/v4/dashboard/day' ),
        bestCustomers:  nsHttpClient.get( '/api/nexopos/v4/dashboard/best-customers' ),
        weeksSummary:   nsHttpClient.get( '/api/nexopos/v4/dashboard/weeks' ),
        bestCashiers:   nsHttpClient.get( '/api/nexopos/v4/dashboard/best-cashiers' ),
        recentOrders:   nsHttpClient.get( '/api/nexopos/v4/dashboard/recent-orders' )
    };
    private _visibleSection: BehaviorSubject<string>;
    private _orderPayments: BehaviorSubject<any[]>;

    constructor() {
        this._day               =   new BehaviorSubject<{}>({});
        this._bestCustomers     =   new BehaviorSubject<[]>([]);
        this._weeksSummary      =   new BehaviorSubject<{}>({});
        this._bestCashiers      =   new BehaviorSubject<[]>([]);
        this._recentOrders      =   new BehaviorSubject<[]>([]);
        this._visibleSection 		= 	new BehaviorSubject<string>('');
        this._orderPayments			=  	new BehaviorSubject<[]>([]);

        for( let index in this._reports ) {
            this.loadReport( index );
        }
    }

    loadReport( type ) {
        return this._reports[ type ]
            .subscribe( result => {
                this[ `_${type}` ].next( result );
            })
    }

    get day() {
        return this._day;
    }

    get bestCustomers() {
        return this._bestCustomers;
    }

    get bestCashiers() {
        return this._bestCashiers;
    }

    get recentOrders() {
        return this._recentOrders;
    }

    get weeksSummary() {
        return this._weeksSummary;
    }
    
    get visibleSection() {
        return this._visibleSection;
    }
    
    changeVisibleSection(section) {
        this._visibleSection.next(section);
    }
    
    get orderPayments() {
    		return this._orderPayments;
    }
    
    setOrderPayments(orderPayments) {
    		this._orderPayments = orderPayments;
    }
}

( window as any ).Dashboard     =   new Dashboard;