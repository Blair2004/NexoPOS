<script>
import { nsHttpClient, nsSnackBar } from '../../bootstrap';

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
            resources: [],

            response: {
                data: [],
                current_page: 0,
                from: 0,
                to: 0,
                next_page_url: '',
                prev_page_url: '',
                path: '',
                per_page: 0,
                total: 0,
                last_page: 0,
                first_page: 0
            },

            queryPage: 1,

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
            return this.response.data.filter( r => r.selected ).length > 0;
        },
        selectedResource() {
            return this.response.data.filter( r => r.selected )[0];
        },
        csrf() {
            return ns.authentication.csrf;
        }
    },
    methods: {
        cancelBulkSelect() {
            this.bulkSelect     =   false;
            this.response.data.forEach( v => v.selected = false );
        },
        deleteSelected() {
            if ( confirm( 'Delete selected resources ?' ) ) {
                return nsHttpClient.post( '/api/nexopos/v4/medias/bulk-delete', {
                        ids: this.response.data
                            .filter( v => v.selected )
                            .map( v => v.id )
                    })
                    .subscribe( result => {
                        nsSnackBar.success( result.message ).subscribe();
                        this.loadGallery();
                    }, error => {
                        nsSnackBar.error( error.message ).subscribe();
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
        loadGallery( page = null ) {
            page = page === null ? this.queryPage : page;
            this.queryPage  =   page;
            nsHttpClient.get( `/api/nexopos/v4/medias?page=${page}` )
                .subscribe( result => {
                    // define default selection status
                    result.data.forEach( resource => resource.selected = false );
                    this.response  =   result;
                })
        },
        selectResource( resource ) {
            if ( ! this.bulkSelect ) {
                this.response.data.forEach( (r, index) => {
                    if ( index !== this.response.data.indexOf( resource ) ) {
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
        <div class="sidebar w-48 bg-gray-200 h-full flex-shrink-0">
            <h3 class="text-xl font-bold text-gray-800 my-4 text-center">Medias Manager</h3>
            <ul>
                <li @click="select( page )" v-for="(page,index) of pages" class="hover:bg-white py-2 px-3 text-gray-700 border-l-8 cursor-pointer" :class="page.selected ? 'bg-white border-blue-400' : 'border-transparent'" :key="index">{{ page.label }}</li>
            </ul>
        </div>
        <div class="content w-full overflow-hidden" v-if="currentPage.name === 'upload'">
            <vue-upload
                ref="upload"
                :drop="true"
                class="w-full h-full flex bg-white shadow"
                v-model="files"
                :multiple="true"
                :headers="{ 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN' : csrf }"
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
                <div class="flex-auto overflow-auto bg-white shadow">
                    <div class="p-2 flex overflow-x-auto flex-wrap">
                        <div v-for="(resource, index) of response.data" :key="index" class="flex -m-2 flex-wrap">
                            <div class="p-2">
                                <div @click="selectResource( resource )" :class="resource.selected ? 'shadow-outline' : ''" class="rounded-lg w-32 h-32 bg-gray-500 m-2 overflow-hidden flex items-center justify-center">
                                    <img class="object-cover h-full" :src="resource.sizes.thumb" :alt="resource.name">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-2 flex flex-shrink-0 justify-between">
                    <div class="flex -mx-2 flex-shrink-0">
                        <div class="px-2 flex-shrink-0 flex">
                            <div class="rounded shadow overflow-hidden border-blue-400 flex text-sm text-gray-700">
                                <button 
                                    v-if="bulkSelect" 
                                    @click="cancelBulkSelect()" 
                                    class="bg-white hover:bg-blue-400 hover:text-white py-2 px-3">
                                    <i class="las la-times"></i>
                                </button>
                                <button 
                                    v-if="hasOneSelected && ! bulkSelect"
                                    @click="bulkSelect = true"
                                    class="bg-white hover:bg-blue-400 hover:text-white py-2 px-3">
                                    <i class="las la-check-circle"></i>                            
                                </button>
                                <button 
                                    v-if="hasOneSelected"
                                    @click="deleteSelected()"
                                    class="bg-red-400 text-white hover:bg-red-500 hover:text-white py-2 px-3">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0 -mx-2">
                        <div class="px-2">
                            <div class="rounded shadow overflow-hidden border-blue-400 flex text-sm text-gray-700">
                                <button :disabled="response.current_page === 1" @click="loadGallery( response.current_page - 1 )" :class="response.current_page === 1 ? 'bg-gray-100 text-gray-600 cursor-not-allowed' : 'bg-white hover:bg-blue-400 hover:text-white'" class="p-2">Previous</button>
                                <hr class="border-r border-gray-700">
                                <button :disabled="response.current_page === response.last_page" @click="loadGallery( response.current_page + 1 )" :class="response.current_page === response.last_page ? 'bg-gray-100 text-gray-600 cursor-not-allowed' : 'bg-white hover:bg-blue-400 hover:text-white'" class="p-2">Next</button>
                            </div>
                        </div>
                        <div class="px-2">
                            <ns-button v-if="popup" type="info">Use Selected</ns-button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="preview" class="w-64 flex-shrink-0" v-if="! bulkSelect && hasOneSelected">
                <div class="h-64 bg-gray-600 flex items-center justify-center">
                    <img :src="selectedResource.sizes.thumb" :alt="selectedResource.name">
                </div>
                <div id="details" class="p-4 text-gray-700 text-sm">
                    <p class="flex flex-col mb-2">
                        <strong class="font-bold block">File Name: </strong><span>{{ selectedResource.name }}</span>
                    </p>
                    <p class="flex flex-col mb-2">
                        <strong class="font-bold block">Uploaded At:</strong><span>{{ selectedResource.created_at }}</span>
                    </p>
                    <p class="flex flex-col mb-2">
                        <strong class="font-bold block">By :</strong><span>{{ selectedResource.user.username }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>