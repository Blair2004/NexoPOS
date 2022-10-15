<script>
import { nsHooks, nsHttpClient, nsSnackBar } from '../../bootstrap';
import popupCloser from "@/libraries/popup-closer";
import { __ } from '@/libraries/lang';
import nsPosConfirmPopupVue from '@/popups/ns-pos-confirm-popup.vue';

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
                label: __( 'Upload' ),
                name: 'upload',
                selected: false,
            }, {
                label: __( 'Gallery' ),
                name : 'gallery',
                selected: true,
            }],
            /**
             * done for demo purposes
             */
            resources: [],

            isDragging: false,

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
        /**
         * when the media is being opened
         * from a popup
         */
        this.popupCloser();

        const gallery   =   this.pages.filter( p => p.name === 'gallery' )[0];

        this.select( gallery );
    },
    watch: {
        files() {
            /**
             * as long as there are file that aren't 
             * yet uploaded. We'll trigger the "active" status.
             */
            this.uploadFiles();
        }
    },
    computed: {
        postMedia() {
            return nsHooks.applyFilters( 'http-client-url', '/api/nexopos/v4/medias' );
        },
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
        },
        isPopup() {
            return typeof this.$popup !== 'undefined';
        },
        user_id() {
            return this.isPopup ? ( this.$popupParams.user_id || 0 ) : 0;
        },
        panelOpened() {
            return ! this.bulkSelect && this.hasOneSelected;
        }
    },
    methods: {
        popupCloser,

        __,

        cancelBulkSelect() {
            this.bulkSelect     =   false;
            this.response.data.forEach( v => v.selected = false );
        },

        /**
         * That trigger a deletion
         * request to the server
         * @param {void}
         * @return {void}
         */
        deleteSelected() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'You\'re about to delete selected resources. Would you like to proceed?' ),
                onAction: ( action ) => {
                    if ( action ) {
                        nsHttpClient.post( '/api/nexopos/v4/medias/bulk-delete', {
                            ids: this.response.data
                                .filter( v => v.selected )
                                .map( v => v.id )
                        })
                        .subscribe({ 
                            next: result => {
                                nsSnackBar.success( result.message ).subscribe();
                                this.loadGallery();
                            },
                            error: error => {
                                nsSnackBar.error( error.message ).subscribe();
                            }
                        });
                    }
                }
            });
        },

        loadUploadScreen() {
            setTimeout( () => {
                this.setDropZone();
            }, 1000 );
        },

        setDropZone() {
            const dropArea      =   document.getElementById( 'dropping-zone' );

            dropArea.addEventListener( 'dragenter', (e) => this.preventDefaults(e), false );
            dropArea.addEventListener( 'dragleave', (e) => this.preventDefaults(e), false );
            dropArea.addEventListener( 'dragover', (e) => this.preventDefaults(e), false );
            dropArea.addEventListener( 'drop', (e) => this.preventDefaults(e), false );

            const dragInEvents  =   [ 'dragenter', 'dragover' ];

            dragInEvents.forEach( (eventName) => {
                dropArea.addEventListener( eventName, () => {
                    this.isDragging     =   true;
                });
            });

            [ 'dragleave', 'drop' ].forEach( eventName => {
                dropArea.addEventListener( eventName, () => {
                    this.isDragging     =   false;
                });
            });

            dropArea.addEventListener( 'drop', (e) => this.handleDrop(e), false );

            this.$refs.files.addEventListener( 'change', (e) => this.processFiles(e.currentTarget.files) );
        },

        async uploadFiles() {
            const uploadableFiles   =   this.files.filter( file => file.uploaded === false && file.progress === 0 && file.failed === false );
            
            for( let i = 0; i < uploadableFiles.length; i++ ) {
                const fileData      =   uploadableFiles[i];

                try {
                    fileData.progress       =   1;

                    const promise   =   await new Promise( ( resolve, reject ) => {
                        const formData      =   new FormData();
                        formData.append( 'file', fileData.file );

                        nsHttpClient.post( `/api/nexopos/v4/medias`, formData, {
                            headers: {
                                "Content-Type": "multipart/form-data"
                            }
                        }).subscribe({
                            next: result => {
                                fileData.uploaded   =   true;
                                fileData.progress   =   100;

                                resolve( result );
                            },
                            error: error => {
                                reject( error );
                            }
                        })
                    })
                } catch( exception ) {
                    fileData.failed     =   true;
                }
            }
        },

        handleDrop( e ) {
            this.processFiles( e.dataTransfer.files );
            e.preventDefault();
            e.stopPropagation();
        },

        preventDefaults( e ) {
            e.preventDefault();
            e.stopPropagation();
        },

        /**
         * Will trigger manual upload
         * @return void
         */
        triggerManualUpload() {
            this.$refs.files.click();
        },

        /***
         * Will filter file and only store
         * file that are images and supported
         * @param FileList files
         */
        processFiles( files ) {
            const arrayFiles    =   Array.from( files );
            const valid         =   arrayFiles.filter( file => [ 'image/png', 'image/gif', 'image/jpg', 'image/jpeg' ].includes( file.type ) );

            valid.forEach( file => {
                this.files.unshift({
                    file,
                    uploaded: false,
                    failed: false,
                    progress: 0
                });
            });
        },

        /**
         * This make sure to select
         * what is the active page on the media.
         * By default we have gallery and upload
         * @param page
         * @return void
         */
        select( page ) {
            this.pages.forEach( page => page.selected = false );
            page.selected   =   true;

            if ( page.name === 'gallery' ) {
                this.loadGallery();
            } else if ( page.name === 'upload' ) {
                this.loadUploadScreen();
            }
        },

        /**
         * This make sure to load the 
         * gallery. That means loading images
         * with a pagination system
         * @param {interger} page
         * @return void
         */
        loadGallery( page = null ) {
            page = page === null ? this.queryPage : page;
            this.queryPage  =   page;
            nsHttpClient.get( `/api/nexopos/v4/medias?page=${page}&user_id=${this.user_id}` )
                .subscribe( result => {
                    // define default selection status
                    result.data.forEach( resource => resource.selected = false );
                    this.response  =   result;
                })
        },

        /**
         * Will event an event with
         * all the selected resources
         * @return void
         */
        useSelectedEntries() {
            this.$popupParams.resolve({
                event: 'use-selected',
                value: this.response.data.filter( entry => entry.selected )
            });
            this.$popup.close();
        },

        /** 
         * this makes sure resources
         * are correctly select when the bulk selection
         * is enabled or not
         * @param {object} resource
         * @return {void}
        */
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
    }
}
</script>
<template>
    <div class="flex md:flex-row flex-col ns-box shadow-xl overflow-hidden" id="ns-media" :class="isPopup ? 'w-6/7-screen h-6/7-screen' : 'w-full h-full'">
        <div class="sidebar w-48 md:h-full flex-shrink-0">
            <h3 class="text-xl font-bold my-4 text-center">{{ __( 'Medias Manager' ) }}</h3>
            <ul class="sidebar-menus flex md:block">
                <li @click="select( page )" v-for="(page,index) of pages" class="py-2 px-3 cursor-pointer border-l-8" :class="page.selected ? 'active' : ''" :key="index">{{ page.label }}</li>
            </ul>
        </div>
        <div class="content w-full overflow-hidden flex" v-if="currentPage.name === 'upload'">
            <div id="dropping-zone" @click="triggerManualUpload()" :class="isDragging ? 'border-dashed border-2' : ''" class="flex flex-auto m-2 p-2 flex-col border-info-primary items-center justify-center">
                <h3 class="text-lg md:text-xl font-bold text-center text-primary mb-4">{{ __( 'Click Here Or Drop Your File To Upload' ) }}</h3>
                <input style="display:none" type="file" name="" multiple ref="files" id="">
                <div class="rounded w-full md:w-2/3 text-primary h-56 overflow-y-auto ns-scrollbar p-2">
                    <ul>
                        <li v-for="(fileData, index) of files" :key="index" class="p-2 mb-2 shadow ns-media-upload-item flex items-center justify-between rounded">
                            <span>{{ fileData.file.name }}</span>
                            <span class="rounded bg-info-primary flex items-center justify-center text-xs p-2">{{ fileData.progress }}%</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="content flex-col w-full overflow-hidden flex" v-if="currentPage.name === 'gallery'">
            <div class="p-2 flex flex-shrink-0 justify-between" v-if="popup">
                <div></div>
                <div>
                    <ns-close-button @click="popup.close()"></ns-close-button>
                </div>
            </div>
            <div class="flex flex-auto overflow-hidden">
                <div class="shadow ns-grid flex flex-auto flex-col overflow-y-auto ns-scrollbar">
                    <div class="flex flex-auto">
                        <div class="p-2 overflow-x-auto">
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4">
                                <div v-for="(resource, index) of response.data" :key="index" class="">
                                    <div class="p-2">
                                        <div @click="selectResource( resource )" :class="resource.selected ? 'ns-media-image-selected ring-4' : ''" class="rounded-lg aspect-square bg-gray-500 m-2 overflow-hidden flex items-center justify-center">
                                            <img class="object-cover h-full" :src="resource.sizes.thumb" :alt="resource.name">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="response.data.length === 0" class="flex flex-auto items-center justify-center">
                            <h3 class="text-2xl font-bold">{{ __( 'Nothing has already been uploaded' ) }}</h3>
                        </div>
                    </div>
                </div>
                <div id="preview" class="ns-media-preview-panel hidden lg:block w-64 flex-shrink-0 ">
                    <div class="h-64 bg-gray-800 flex items-center justify-center">
                        <img v-if="panelOpened" :src="selectedResource.sizes.thumb" :alt="selectedResource.name">
                    </div>
                    <div id="details" class="p-4 text-gray-700 text-sm" v-if="panelOpened">
                        <p class="flex flex-col mb-2">
                            <strong class="font-bold block">{{ __( 'File Name' ) }}: </strong><span>{{ selectedResource.name }}</span>
                        </p>
                        <p class="flex flex-col mb-2">
                            <strong class="font-bold block">{{ __( 'Uploaded At' ) }}:</strong><span>{{ selectedResource.created_at }}</span>
                        </p>
                        <p class="flex flex-col mb-2">
                            <strong class="font-bold block">{{ __( 'By' ) }} :</strong><span>{{ selectedResource.user.username }}</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="p-2 flex ns-media-footer flex-shrink-0 justify-between">
                <div class="flex -mx-2 flex-shrink-0">
                    <div class="px-2 flex-shrink-0 flex">
                        <div class="rounded shadow overflow-hidden flex text-sm">
                            <div class="ns-button info" v-if="bulkSelect" >
                                <button 
                                    @click="cancelBulkSelect()" 
                                    class="py-2 px-3">
                                    <i class="las la-times"></i>
                                </button>
                            </div>
                            <div class="ns-button info" v-if="hasOneSelected && ! bulkSelect">
                                <button 
                                    @click="bulkSelect = true"
                                    class="py-2 px-3">
                                    <i class="las la-check-circle"></i>                            
                                </button>
                            </div>
                            <div class="ns-button error" v-if="hasOneSelected">
                                <button 
                                    @click="deleteSelected()"
                                    class="py-2 px-3">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0 -mx-2 flex">
                    <div class="px-2">
                        <div class="rounded shadow overflow-hidden flex text-sm">
                            <div class="ns-button" :class="response.current_page === 1 ? 'disabled cursor-not-allowed' : 'info'">
                                <button :disabled="response.current_page === 1" @click="loadGallery( response.current_page - 1 )" class="p-2">{{ __( 'Previous' ) }}</button>
                            </div>
                            <hr class="border-r border-gray-700">
                            <div class="ns-button" :class="response.current_page === response.last_page ? 'disabled cursor-not-allowed' : 'info'">
                                <button :disabled="response.current_page === response.last_page" @click="loadGallery( response.current_page + 1 )" class="p-2">{{ __( 'Next' ) }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="px-2" v-if="popup && hasOneSelected">
                        <div class="ns-button info">
                            <button class="rounded shadow p-2 text-sm" @click="useSelectedEntries()">{{ __( 'Use Selected' ) }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>