declare const ns;

export default class Url {
    
    private url: string;

    constructor() {
        this.url    =   ns.base_url;
    }

    get( path ) {
        return this.url + path;
    }
}