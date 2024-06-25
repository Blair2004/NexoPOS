<template>
    <div class="ns-button default">
        <button @click="openRegisterOptions()" class="rounded shadow flex-shrink-0 h-12 flex items-center px-2 py-1 text-sm">
            <i class="mr-1 text-xl las la-cash-register"></i>
            <span>{{ name }}</span>
        </button>
    </div>
</template>
<script>
import nsPosCashRegistersPopupVue from '~/popups/ns-pos-cash-registers-popup.vue';
import nsPosCashRegistersOptionsPopupVue from '~/popups/ns-pos-cash-registers-options-popup.vue';
import { nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';

export default {
    data() {
        return {
            order: null,
            name: '',
            selectedRegister: null,
            orderSubscriber: null,
            settingsSubscriber: null,
        }
    },
    watch: {
    },
    methods: {
        __,

        async openRegisterOptions() {
            try {
                const response  =   await new Promise( ( resolve, reject ) => {
                    Popup.show( nsPosCashRegistersOptionsPopupVue, { resolve, reject })
                });

                if ( response.button === 'close_register' ) {
                    delete this.settings.register;
                    POS.settings.next( this.settings );
                    POS.reset();
                } 
            } catch( error ) {
                if ( Object.keys( error ).length > 0 ) {
                    nsSnackBar.error( error.message ).subscribe();
                }
            }
        },
        registerInitialQueue() {
            POS.initialQueue.push( () => new Promise( async ( resolve, reject ) => {
                try {
                    const response  =   await new Promise( ( resolve, reject ) => {
                        if ( this.settings.register === undefined ) {
                            return Popup.show( nsPosCashRegistersPopupVue, { resolve, reject }, {
                                closeOnOverlayClick: false
                            });
                        }

                        resolve({ data: { register: this.settings.register } });
                    });

                    /**
                     * we define here the register that will be used
                     * throughout the orders send to the server
                     */
                    POS.set( 'register', response.data.register ); 
                    this.setRegister( response.data.register );

                    resolve( response );
                } catch( exception ) {
                    if ( exception === false ) {
                        return reject({
                            status: 'error',
                            message: __( 'You must choose a register before proceeding.' )
                        });
                    }
                    reject( exception );
                }
            }));
        },
        setButtonName() {
            if ( this.settings.register === undefined ) {
                return this.name   =   __( 'Cash Register' );
            }

            this.name   =   __( `Cash Register : {register}` ).replace( '{register}', this.settings.register.name );
        },
        setRegister( register ) {
            if ( register !== undefined ) {
                /**
                 * This will update the register ID once we've
                 * successfully loaded the opened cash register
                 */
                const order             =   POS.order.getValue();
                order.register_id       =   register.id; 
                POS.order.next( order );
            }
        }
    },
    unmounted() {
        this.orderSubscriber.unsubscribe();
        this.settingsSubscriber.unsubscribe();
    },
    mounted() {
        this.registerInitialQueue();

        this.orderSubscriber    =   POS.order.subscribe( order => {
            this.order  =   order;
        });

        this.settingsSubscriber     =   POS.settings.subscribe( settings => {
            this.settings           =   settings;

            this.setRegister( this.settings.register );   

            this.setButtonName();
        });
    }
}
</script>