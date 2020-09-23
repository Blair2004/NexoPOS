<script>
import FormValidation from '../../../../libraries/form-validation';
export default {
    name: 'ns-pos-payment',
    props: [ 'popup' ],
    data() {
        return { 
            foo: 'bar', 
            count: 0,
            validation: new FormValidation,
            field: null
        }
    },
    mounted() {
        this.field  =   this.validation.createFields([{
            type: 'media',
            label: 'Media',
            name: 'media',
            description: 'open media',
        }])[0];

        this.popup.event.subscribe( action => {
            switch( action.event ) {
                case 'click-overlay': 
                    this.closePopup();
                break;
            }
        });
    },
    methods: {
        closePopup() {
            this.popup.close();
        }
    }
}
</script>
<template>
    <div class="h-full w-full flex">
        <div class="w-56 bg-gray-300 h-full">
            <h3 class="text-xl text-center my-8 text-gray-700">Payments Gateway</h3>
            <ul>
                <li class="hover:bg-gray-400 py-2 px-3 text-gray-700">Cash Payment</li>
                <li class="hover:bg-gray-400 py-2 px-3 text-gray-700">Card Swipe</li>
            </ul>
        </div>
        <div>
            
        </div>
    </div>
</template>