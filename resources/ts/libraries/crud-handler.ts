export default class CrudHandler {
    instances: {[ key: string] : any };

    constructor() {
        this.instances  =   new Object;
    }

    getInstance( src ) {
        return this.instances[ src ];
    }

    defineInstance( src, instance ) {
        this.instances[ src ]   =   instance;
    }
}