<script>
import { nsHttpClient } from '../../bootstrap';

const VueUpload     =   require( 'vue-upload-component' );

export default {
    name: 'ns-media',
    props: [ 'popup' ],
    components: {
        VueUpload
    },
    data() {
        return {
            pages: [{
                label: 'Upload',
                name: 'upload',
                selected: false,
            }, {
                label: 'Gallery',
                name : 'gallery',
                selected: true,
            }],
            /**
             * done for demo purposes
             */
            resources: (new Array(100)).fill( '' ).map( ( v,i ) => {
                return {
                    selected: false
                }
            }),

            /**
             * determine wether the bulk
             * selector is enabled or not.
             */
            bulkSelect: false,
            files: [],
        }
    },
    mounted() {
        console.log( ns );
        /**
         * when the media is being opened
         * from a popup
         */
        if ( this.popup ) {
            this.popup.event.subscribe( action => {
                if ( action.event === 'click-overlay' ) {
                    this.popup.close();
                }
            });
        }

        console.log( this.resources );

        const gallery   =   this.pages.filter( p => p.name === 'gallery' )[0];
        this.select( gallery );
    },
    watch: {
        files() {
            /**
             * as long as there are file that aren't 
             * yet uploaded. We'll trigger the "active" status.
             */
            if ( this.files.filter( f => f.progress === '0.00' ).length > 0 ) {
                this.$refs.upload.active = true;
            }
        }
    },
    computed: {
        currentPage() {
            return this.pages.filter( page => page.selected )[0];
        },
        hasOneSelected() {
            return this.resources.filter( r => r.selected ).length > 0;
        }
    },
    methods: {
        cancelBulkSelect() {
            this.bulkSelect     =   false;
            this.resources.forEach( v => v.selected = false );
        },
        deleteSelected() {
            if ( confirm( 'Delete selected resources ?' ) ) {
                return nsHttpClient.post( '/api/v4/medias/bulk-delete', {
                        ids: this.resources
                        .filter( v => v.selected )
                        .map( v => v.id )
                    })
                    .subscribe( result => {

                    })
            }
        },
        select( page ) {
            this.pages.forEach( page => page.selected = false );
            page.selected   =   true;

            if ( page.name === 'gallery' ) {
                this.loadGallery();
            }
        },
        loadGallery() {

        },
        selectResource( resource ) {
            if ( ! this.bulkSelect ) {
                this.resources.forEach( (r, index) => {
                    if ( index !== this.resources.indexOf( resource ) ) {
                        r.selected = false;
                    }
                });
            }

            resource.selected   =   ! resource.selected;
        },

        /**
         * Has changed
         * @param  Object|undefined   newFile   Read only
         * @param  Object|undefined   oldFile   Read only
         * @return undefined
         */
        inputFile: function (newFile, oldFile) {
            if (newFile && oldFile && !newFile.active && oldFile.active) {
                // Get response data
                console.log('response', newFile.response)
                if (newFile.xhr) {
                    //  Get the response status code
                    console.log('status', newFile.xhr.status)
                }
            }
        },
        /**
         * Pretreatment
         * @param  Object|undefined   newFile   Read and write
         * @param  Object|undefined   oldFile   Read only
         * @param  Function           prevent   Prevent changing
         * @return undefined
         */
        inputFilter: function (newFile, oldFile, prevent) {
            if (newFile && !oldFile) {
                // Filter non-image file
                if (!/\.(jpeg|jpe|jpg|gif|png|webp)$/i.test(newFile.name)) {
                    return prevent()
                }
            }

            // Create a blob field
            newFile.blob = ''
            let URL = window.URL || window.webkitURL
            if (URL && URL.createObjectURL) {
                newFile.blob = URL.createObjectURL(newFile.file)
            }
        }
    }
}
</script>
<template>
    <div class="flex h-full overflow-hidden">
        <div class="sidebar w-48 bg-gray-300 h-full flex-shrink-0">
            <h3 class="text-xl font-bold text-gray-800 my-8 text-center">Media Manager</h3>
            <ul>
                <li @click="select( page )" v-for="(page,index) of pages" class="hover:bg-gray-400 py-2 px-3 text-gray-700 border-l-8 cursor-pointer" :class="page.selected ? 'bg-gray-400 border-blue-400' : 'border-transparent'" :key="index">{{ page.label }}</li>
            </ul>
        </div>
        <div class="content w-full" v-if="currentPage.name === 'upload'">
            <vue-upload
                ref="upload"
                :drop="true"
                class="w-full h-full flex"
                v-model="files"
                :multiple="true"
                :headers="{ 'X-Requested-With': 'XMLHttpRequest' }"
                accept="image/*"
                post-action="/api/nexopos/v4/medias"
                @input-file="inputFile"
                @input-filter="inputFilter">
                <div class="border-dashed border-2 flex flex-auto m-2 p-2 flex-col border-blue-400 items-center justify-center">
                    <h3 class="text-3xl font-bold text-gray-600 mb-4">Click Here Or Drop Your File To Upload</h3>
                    <div class="rounded w-full md:w-2/3 text-gray-700 bg-gray-500 h-56 overflow-y-auto p-2">
                        <ul>
                            <li v-for="(file, index) of files" :key="index" class="p-2 mb-2 shadow bg-white flex items-center justify-between rounded">
                                <span>{{ file.name }}</span>
                                <span class="rounded bg-blue-400 flex items-center justify-center text-xs p-2">{{ file.progress }}%</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </vue-upload>
        </div>
        <div class="content flex w-full" v-if="currentPage.name === 'gallery'">
            <div id="grid" class="content flex flex-col w-full overflow-hidden">
                <div class="p-2 flex overflow-x-auto flex-wrap">
                    <div v-for="(resource, index) of resources" :key="index" class="flex -m-2 flex-wrap">
                        <div class="p-2">
                            <div @click="selectResource( resource )" :class="resource.selected ? 'shadow-outline' : ''" class="rounded-lg w-32 h-32 bg-gray-500 m-2"></div>
                        </div>
                    </div>
                </div>
                <div class="p-2 bg-gray-300 flex flex-shrink-0 justify-between">
                    <div class="flex -mx-2 flex-shrink-0">
                        <div class="px-2 flex-shrink-0 flex" v-if="bulkSelect">
                            <ns-button @click="cancelBulkSelect()">
                                <i class="las la-times"></i>
                            </ns-button>
                        </div>
                        <div class="px-2 flex-shrink-0 flex" v-if="hasOneSelected && ! bulkSelect">
                            <ns-button @click="bulkSelect = true" type="info">
                                <i class="las la-check-circle"></i>
                            </ns-button>
                        </div>
                        <div class="px-2 flex-shrink-0 flex" v-if="hasOneSelected">
                            <ns-button @click="deleteSelected()" type="danger">
                                <i class="las la-trash"></i>
                            </ns-button>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0">
                        <ns-button v-if="popup" type="info">Use Selected</ns-button>
                    </div>
                </div>
            </div>
            <div id="preview" class="w-64 bg-gray-300 flex-shrink-0" v-if="! bulkSelect && hasOneSelected">
                <div class="h-64 bg-gray-600 flex items-center justify-center">

                </div>
                <div id="details" class="p-4 text-gray-700">
                    <strong class="font-bold mb-2 block">File Name: </strong>
                    <strong class="font-bold mb-2 block">Uploaded At: </strong>
                    <strong class="font-bold mb-2 block">By : </strong>
                </div>
            </div>
        </div>
    </div>
</template>