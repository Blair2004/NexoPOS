export class Popup {
    private config  =   {
        primarySelector     :   '',
    }; 

    private container   =   document.createElement( 'div' );
    private popupBody   =   document.createElement( 'div' );
    private vue: Vue;
    private primarySelector: HTMLDivElement;

    constructor( config ) {
        this.config             =   Object.assign( this.config, config );
        this.primarySelector    =   document.querySelector( this.config.primarySelector );
    }

    show() {
        this.primarySelector.style.filter   =   'blur(10px)';
        this.container.setAttribute( 'class', 'absolute top-0 left-0 w-full h-full flex items-center justify-center' );
        this.container.id                   =   'popup-container';
        this.popupBody.setAttribute( 'class', 'shadow-lg h-half w-1/2 bg-white' );
        this.popupBody.innerHTML            =   '<component v-bind:is="popupComponent"></component>'
        this.container.appendChild( this.popupBody );  
        document.body.appendChild( this.container );

        this.vue    =   new Vue({
            el: this.container.id
        })
    }

    close() {
        this.primarySelector.style.filter   =   'blur(0px)';
        const element   =   document.querySelector( '#popup-wrapper' );
        // make some animation
        element.remove();
    }
}