const { Vue, EventEmitter, nsHttpClient }   =   require( '../bootstrap' );

const nsCrud    =   Vue.component( 'ns-crud-form', {
    data: () => {
        return {
            form: {},
            globallyChecked: false,
            rows: []
        }
    }, 
    mounted() {
        this.loadForm();
    },
    props: [ 'src', 'create-link' ],
    computed: {
        activeTabFields() {
            for( identifier in this.form.tabs ) {
                if ( this.form.tabs[ identifier ].active ) {
                    return this.form.tabs[ identifier ].fields;
                }
            }
            return [];
        }
    },
    methods: {
        toggle( identifier ) {
            for( key in this.form.tabs ) {
                this.form.tabs[ key ].active    =   false;
            }
            this.form.tabs[ identifier ].active     =   true;
        },
        handleShowOptions( e ) {
            this.rows.forEach( row => {
                if ( row.$id !== e.$id ) {
                    row.$toggled    =   false;
                }
            });
        },
        handleGlobalChange( event ) {
            this.globallyChecked    =   event;
            this.rows.forEach( r => r.$checked = event );
        },
        loadForm() {
            const request   =   nsHttpClient.get( `${this.src}` );
            request.subscribe( f => {
                this.form    =   this.parseForm( f.data.form );
                console.log( this.form );
            });
        },
        parseForm( form ) {
            form.main.value     =   form.main.value === undefined ? '' : form.main.value;
            let index           =   0;

            for( key in form.tabs ) {
                if ( index === 0 ) {
                    form.tabs[ key ].active  =   true;
                }

                form.tabs[ key ].active = form.tabs[ key ].active === undefined ? false : form.tabs[ key ].active
                index++;
            }

            return form;
        }
    },
    template: `
    <div class="form flex-auto" id="crud-form">
        <div v-if="Object.values( form ).length === 0" class="flex items-center justify-center h-full">
            <ns-spinner />
        </div>
        <div v-if="Object.values( form ).length > 0">
            <div class="flex flex-col">
                <div class="flex justify-between items-center">
                    <label for="title" class="font-bold my-2 text-gray-700"><slot name="title">No title Provided</slot></label>
                    <div for="title" class="text-sm my-2 text-gray-700">
                        <button class="rounded-full border border-gray-400 hover:bg-red-600 hover:text-white bg-white px-2 py-1">Return</button>
                    </div>
                </div>
                <div class="flex border-2 border-blue-500 rounded overflow-hidden">
                    <input v-model="form.main.value" type="text" class="flex-auto text-gray-700 outline-none h-10 px-2">
                    <button class="px-4 h-10 bg-blue-500 text-white border-l border-gray-400"><slot name="save">Save</slot></button>
                </div>
            </div>
            <div id="tabs-container" class="my-5">
                <div class="header flex" style="margin-bottom: -1px;">
                    <div v-for="( tab , identifier ) of form.tabs" @click="toggle( identifier )" :class="tab.active ? 'border-b-0 bg-white' : 'border bg-gray-200'" class="tab rounded-tl rounded-tr border border-gray-400  px-3 py-2 text-gray-700 cursor-pointer" style="margin-right: -1px">{{ tab.label }}</div>
                </div>
                <div v-for="tab of form.tabs" class="border border-gray-400 p-4 bg-white">
                    <div class="-mx-4 flex flex-wrap">
                        <div class="px-4 w-full md:w-1/2 lg:w-1/3" v-for="field of activeTabFields">
                            <div class="input-field flex flex-col">
                                <label for="" class="font-bold text-gray-700 mb-2">{{ field.label }}</label>
                                <input type="text" v-model="field.value" class="h-10 border px-2 border-gray-400 bg-gray-100">
                                <p class="text-xs py-2 text-gray-600">{{ field.description || '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `,
});

module.exports   =   nsCrud;