import { Popup } from './popup';
const MediaComponent     =   require( './../pages/dashboard/ns-media' ).default;

export class Media {
    private popup;

    constructor(
        private field?: string,
        private primarySelector?: string
    ) {}

    open() {
        this.popup      =   new Popup({
            popupClass: 'w-4/5 h-4/5-screen shadow-lg bg-white'
        });

        this.popup.open( MediaComponent );
    }
}