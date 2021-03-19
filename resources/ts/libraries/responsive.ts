
export class Responsive {
    private screenIs: string;

    constructor() {
        this.detect();
    }

	detect(){
		if ( window.innerWidth < 544 ) {
			this.screenIs         =   'xs';
		} else if ( window.innerWidth >= 544 && window.innerWidth < 768 ) {
			this.screenIs         =   'sm';
		} else if ( window.innerWidth >= 768 && window.innerWidth < 992 ) {
			this.screenIs         =   'md';
		} else if ( window.innerWidth >= 992 && window.innerWidth < 1200 ) {
			this.screenIs         =   'lg';
		} else if ( window.innerWidth >= 1200 ) {
			this.screenIs         =   'xl';
		}
	}

	is( value?: string | undefined ): boolean | string {
		if ( value === undefined ) {
			return this.screenIs;
		} else {
			return this.screenIs === value;
		}
	}
};