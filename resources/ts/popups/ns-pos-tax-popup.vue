<template>
    <div class="bg-white shadow-lg w-95vw md:w-3/5-screen lg:w-2/5-screen">
        <div class="p-2 flex justify-between items-center border-b border-gray-200">
            <h3 class="text-blog">Tax & Summary</h3>
            <div>
                <ns-close-button @click="closePopup()"></ns-close-button>
            </div>
        </div>
        <div class="p-2 bg-gray-50">
            <ns-tabs :active="activeTab" @changeTab="changeActive( $event )">
                <ns-tabs-item label="Settings" identifier="settings" :active="true">
                    <div class="p-2 border-b border-gray-200">
                        <ns-field v-for="(field,index) of group_fields" :field="field" :key="index"></ns-field>
                    </div>
                    <div class="flex justify-end p-2">
                        <ns-button @click="saveTax()" type="info">Save</ns-button>
                    </div>
                </ns-tabs-item>
                <ns-tabs-item label="Summary" identifier="summary" :active="false">
                    <template v-if="order">
                        <div v-for="tax of order.taxes" :key="tax.id" class="border-blue-200 mb-2 border bg-blue-100 shadow p-2 w-full flex justify-between items-center text-gray-700">
                            <span>{{ tax.tax_name }}</span>
                            <span>{{ tax.tax_value | currency  }}</span>
                        </div>
                        <div class="p-2 text-center text-gray-600" v-if="order.taxes.length === 0">No tax is active</div>
                    </template>
                </ns-tabs-item>
            </ns-tabs>
        </div>
    </div>
</template>
<script>
import { nsHttpClient } from '@/bootstrap';
import FormValidation from '@/libraries/form-validation';
import popupCloser from '@/libraries/popup-closer';
import popupResolver from '@/libraries/popup-resolver';
export default {
    name: 'ns-pos-tax-popup',
    data() {
        return {
            validation: new FormValidation,
            tax_group: [],
            order: null,
            orderSubscriber: null,
            tax_groups: [],
            activeTab: '',
            group_fields: [
                {
                    label: 'Select Tax',
                    name: 'tax_group_id',
                    description: 'Define the tax that apply to the sale.',
                    type: 'select',
                    value: '',
                    options: []
                }, {
                    label: 'Type',
                    name: 'tax_type',
                    value: '',
                    description: 'Define how the tax is computed',
                    type: 'select',
                    options: [{
                        label: 'Exclusive',
                        value: 'exclusive',
                    }, {
                        label: 'Inclusive',
                        value: 'inclusive'
                    }]
                }
            ]
        }
    },
    mounted() {
        this.loadGroups();
        this.popupCloser();

        this.activeTab      =   this.$popupParams.activeTab || 'settings';

        this.group_fields.forEach( field => {
            field.value     =   this.$popupParams[ field.name ] || undefined;
        });

        this.orderSubscriber    =   POS.order.subscribe( order => {
            this.order      =   order;
        });
    },
    destroyed() {
        this.orderSubscriber.unsubscribe();
    },
    methods: {
        popupCloser,
        popupResolver,

        changeActive( active ) {
            this.activeTab  =   active;
        },

        closePopup() {
            this.popupResolver(false);
        },

        saveTax() {
            const fields    =   this.validation.extractFields( this.group_fields );
            this.popupResolver( fields );
        },  
        loadGroups() {
            nsHttpClient.get( `/api/nexopos/v4/taxes/groups` )
                .subscribe( groups => {
                    this.groups     =   groups;
                    this.group_fields.forEach( field => {
                        if ( field.name === 'tax_group_id' ) {
                            field.options   =   this.groups.map( group => {
                                return {
                                    label: group.name,
                                    value: group.id
                                }
                            });
                        }
                    })
                })
        }
    }
}
</script>