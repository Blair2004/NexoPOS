<script lang="ts">
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import popupCloser from "~/libraries/popup-closer";
import { __ } from '~/libraries/lang';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { fileIcons } from '~/shared/file-icons';
import { Popup } from '~/libraries/popup';
import { nsAlertPopup } from '~/components/components';

declare const nsHooks;

export default {
    name: 'ns-media',
    props: [ 'popup' ],
    data() {
        return {
            searchFieldDebounce: null,
            searchField: '',
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

            fileIcons,

            queryPage: 1,

            /**
             * determine whether the bulk
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
        const gallery = this.pages.filter(p => p.name === 'gallery')[0];
        this.select(gallery);
        // Add paste event listener for clipboard image upload
        window.addEventListener('paste', this.handlePasteUpload);
    },
    beforeDestroy() {
        // Remove paste event listener
        window.removeEventListener('paste', this.handlePasteUpload);
    },
    watch: {
        searchField() {
            clearTimeout( this.searchFieldDebounce );
            this.searchFieldDebounce    =   setTimeout( () => {
                this.loadGallery(1);
            }, 500 );
        }
    },
    computed: {
        postMedia() {
            return nsHooks.applyFilters( 'http-client-url', '/api/medias' );
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
            return typeof this.popup !== 'undefined';
        },
        user_id() {
            return this.isPopup ? ( this.popup.params.user_id || 0 ) : 0;
        },
        panelOpened() {
            return ! this.bulkSelect && this.hasOneSelected;
        },
        popupInstance() {
            return this.popup;
        }
    },
    methods: {
        popupCloser,

        __,

        cancelBulkSelect() {
            this.bulkSelect     =   false;
            this.response.data.forEach( v => v.selected = false );
        },

        openError( fileData ) {
            Popup.show( nsAlertPopup, {
                title: __( 'An error occured' ),
                message: fileData.error.message || __( 'An unexpected error occured.' )
            });
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
                        nsHttpClient.post( '/api/medias/bulk-delete', {
                            ids: this.response.data
                                .filter( v => v.selected )
                                .map( v => v.id )
                        })
                        .subscribe({
                            next: result => {
                                nsSnackBar.success( result.message );
                                this.loadGallery();
                            },
                            error: error => {
                                nsSnackBar.error( error.message );
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

                        nsHttpClient.post( `/api/medias`, formData, {
                            headers: {
                                "Content-Type": "multipart/form-data"
                            }
                        }).subscribe({
                            next: result => {
                                fileData.uploaded   =   true;
                                fileData.progress   =   100;
                                nsSnackBar.success( 
                                    result.message || __( 'File uploaded successfully.' ),
                                    __( 'OK' )
                                );
                                this.loadGallery(); // Refresh gallery to show new file
                                resolve( result );
                            },
                            error: error => {
                                uploadableFiles[i].failed   =   true;
                                uploadableFiles[i].error    =   error;
                                nsSnackBar.error( 
                                    error.message || __( 'Failed to upload file.' ),
                                    __( 'OK' )
                                );
                                reject( error );
                            }
                        })
                    })
                } catch( exception ) {
                    fileData.failed     =   true;
                    nsSnackBar.error( 
                        __( 'An unexpected error occurred during file upload.' ),
                        __( 'OK' )
                    );
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

        getAllParents( elem ) {
            let parents = [];
            while (elem.parentNode && elem.parentNode.nodeName.toLowerCase() != 'body') {
                elem = elem.parentNode;
                parents.push(elem);
            }

            return parents;
        },

        /**
         * Will trigger manual upload
         * @return void
         */
        triggerManualUpload( $event ) {
            const element = $event.target;
            
            if ( element !== null ) {
                /**
                 * We'll retrieve all parents and their classes
                 * to make sure it's not a children of ns-scrollbar
                 */
                const parents           =   this.getAllParents( element );
                const parentsClasses    =   parents.map( parent => {
                    const classes       =   parent.getAttribute( 'class' );
                    
                    if ( classes ) {
                        return classes.split( ' ' );
                    }
                });

                /**
                 * the clicked item should also add it's own classes
                 */
                if ( element.getAttribute( 'class' ) ) {
                    const classes   =   element.getAttribute( 'class' ).split( ' ' );
                    parentsClasses.push( classes );
                }

                // If the item click is not a children of ns-scrollbar or ns-scrollbar itself... 
                // we can open the popup.
                if ( ! parentsClasses.flat().includes( 'ns-scrollbar' ) ) {
                    this.$refs.files.click();
                }
            };
        },

        /***
         * Will filter file and only store
         * file that are images and supported
         * @param FileList files
         */
        processFiles( files ) {
            console.log({ files })
            const arrayFiles    =   Array.from( files );
            const valid         =   arrayFiles.filter( file => {
                console.log( this );
                return Object.values( window.ns.medias.mimes ).includes( file.type );
            });

            const invalidFiles = arrayFiles.length - valid.length;
            
            if (invalidFiles > 0) {
                nsSnackBar.error(
                    invalidFiles === 1 
                        ? __( '1 file was rejected due to invalid file type.' )
                        : __( '%s files were rejected due to invalid file type.' ).replace('%s', invalidFiles.toString()),
                    __( 'OK' )
                );
            }

            valid.forEach( file => {
                this.files.unshift({
                    file,
                    uploaded: false,
                    failed: false,
                    progress: 0
                });
            });
            
            // Call uploadFiles only once after adding new files
            if (valid.length > 0) {
                this.uploadFiles();
            } else if (arrayFiles.length > 0 && valid.length === 0) {
                nsSnackBar.error(
                    __( 'No valid files selected. Please select supported file types.' ),
                    __( 'OK' )
                );
            }
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
            nsHttpClient.get( `/api/medias?page=${page}&user_id=${this.user_id}${this.searchField.length > 0 ? `&search=${this.searchField}` : `` }` )
                .subscribe({
                    next: result => {
                        // define default selection status
                        result.data.forEach( resource => resource.selected = false );
                        this.response  =   result;
                    },
                    error: error => {
                        nsSnackBar.error( 
                            error.message || __( 'An error occurred while loading the media gallery.' ), 
                            __( 'OK' ) 
                        );
                        console.error('Media gallery loading error:', error);
                    }
                })
        },

        submitChange( field, selectedResource ) {
            nsHttpClient.put( `/api/medias/${selectedResource.id}`, {
                name: field.srcElement.textContent
            }).subscribe({
                next: result => {
                    selectedResource.fileEdit   =   false;
                    nsSnackBar.success( result.message, 'OK' );
                },
                error: error => {
                    selectedResource.fileEdit   =   false;
                    nsSnackBar.success( error.message || __( 'An unexpected error occured.' ), 'OK' );
                }
            });
        },

        /**
         * Will event an event with
         * all the selected resources
         * @return void
         */
        useSelectedEntries() {
            this.popup.params.resolve({
                event: 'use-selected',
                value: this.response.data.filter( entry => entry.selected )
            });
            this.popup.close();
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

            resource.fileEdit   =   false;
            resource.selected   =   ! resource.selected;
        },

        /**
         * Returns wether the provided media 
         * is an image or not.
         * @param {object} media 
         */
        isImage( media ) {
            const imageExtensions   =   Object.keys( ns.medias.imageMimes );
            return imageExtensions.includes( media.extension );
        },

        handlePasteUpload(e: ClipboardEvent) {
            // Only handle if upload tab is active
            if (this.currentPage.name !== 'upload') return;
            if (!e.clipboardData) return;
            
            try {
                const items = e.clipboardData.items;
                let hasValidFile = false;
                
                for (let i = 0; i < items.length; i++) {
                    const item = items[i];
                    if (item.kind === 'file' && item.type.startsWith('image/')) {
                        const file = item.getAsFile();
                        if (file) {
                            hasValidFile = true;
                            this.files.unshift({
                                file,
                                uploaded: false,
                                failed: false,
                                progress: 0
                            });
                        }
                    }
                }
                
                if (hasValidFile) {
                    this.uploadFiles();
                } else {
                    nsSnackBar.error( 
                        __( 'No valid image found in clipboard.' ),
                        __( 'OK' )
                    );
                }
            } catch (error) {
                nsSnackBar.error( 
                    __( 'Error processing clipboard content.' ),
                    __( 'OK' )
                );
                console.error('Clipboard paste error:', error);
            }
        },
    }
}
</script>
<template>
    <div class="flex md:flex-row flex-col ns-box overflow-hidden" id="ns-media" :class="isPopup ? 'w-[85.71vw] h-[95vh] shadow-xl' : 'm-4 w-auto h-full rounded-lg'">
        <div class="sidebar w-48 md:h-full flex-shrink-0">
            <h3 class="text-xl font-bold my-4 text-center">{{ __( 'Medias Manager' ) }}</h3>
            <ul class="sidebar-menus flex md:block mt-8">
                <li @click="select( page )" v-for="(page,index) of pages" class="py-2 px-3 cursor-pointer border-l-8" :class="page.selected ? 'active' : ''" :key="index">{{ page.label }}</li>
            </ul>
        </div>
        <div class="content flex-auto w-full flex-col overflow-hidden flex" v-if="currentPage.name === 'upload'">
            <div class="p-2 flex bg-box-background flex-shrink-0 justify-between" v-if="isPopup">
                <div></div>
                <div>
                    <ns-close-button @click="popupInstance.close()"></ns-close-button>
                </div>
            </div>
            <div id="dropping-zone" @click="triggerManualUpload( $event )" :class="isDragging ? 'border-dashed border' : ''" class="flex flex-auto m-2 p-2 flex-col border-info-primary items-center justify-center">
                <h3 class="cursor-pointer font-bold text-center text-fontcolor mb-4">{{ __( 'Click here or drop your files to upload.' ) }}</h3>
                <input style="display:none" type="file" name="" multiple ref="files" id="">
                <div class="rounded ns-box rounded-lg shadow w-full md:w-2/3 text-fontcolor p-2">
                    <div class="h-56 overflow-y-auto ns-scrollbar">
                        <ul v-if="files.length > 0">
                            <li v-for="(fileData, index) of files" :class="fileData.failed === false ? 'border-info-secondary' : 'border-error-secondary'" :key="index" class="p-2 mb-2 border-b-2 flex items-center justify-between">
                                <span>{{ fileData.file.name }}</span>
                                <span v-if="fileData.failed === false" class="rounded bg-info-primary flex items-center justify-center text-xs p-2">{{ fileData.progress }}%</span>
                                <div @click="openError( fileData )" v-if="fileData.failed === true" class="rounded bg-error-primary hover:bg-error-secondary hover:text-white flex items-center justify-center text-xs p-2 cursor-pointer"><i class="las la-eye"></i> <span class="ml-2">{{ __( 'See Error' ) }}</span></div>
                            </li>
                        </ul>
                        <div v-if="files.length === 0" class="h-full w-full items-center justify-center flex text-center text-soft-tertiary">
                            {{ __( 'Your uploaded files will displays here.' ) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content flex-auto flex-col w-full overflow-hidden flex" v-if="currentPage.name === 'gallery'">
            <div class="p-2 flex bg-box-background flex-shrink-0 justify-between" v-if="isPopup">
                <div></div>
                <div>
                    <ns-close-button @click="popupInstance.close()"></ns-close-button>
                </div>
            </div>
            <div class="flex flex-auto overflow-hidden">
                <div class="shadow flex-auto flex flex-col">
                    <div class="p-2 border-b border-input-edge">
                        <div class="ns-input border border-input-edge overflow-hidden rounded flex">
                            <input id="search" type="text" v-model="searchField" :placeholder="__( 'Search Medias' )" class="px-4 block w-full sm:text-sm sm:leading-5 h-10">
                            <div class="flex items-center justify-center w-20 p-1" v-if="searchField.length > 0">
                                <button @click="searchField = ''" class="h-full w-full rounded-tr rounded-br overflow-hidden">{{ __( 'Cancel' ) }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-auto overflow-hidden">
                        <div v-if="response.data.length > 0" class="p-2 flex-auto overflow-y-auto ns-scrollbar">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-1 lg:grid-cols-4 xl:grid-cols-6">
                                <div v-for="(resource, index) of response.data" :key="index">
                                    <div>
                                        <div @click="selectResource( resource )" :class="resource.selected ? 'ns-media-image-selected border-secondary ' : 'border-transparent'" class="border-4 aspect-square bg-secondary/50 overflow-hidden flex items-center justify-center">
                                            <img v-if="isImage( resource )" class="object-cover h-full" :src="resource.sizes.thumb" :alt="resource.name"/>
                                            <template v-if="! isImage( resource )" class="object-cover h-full" :alt="resource.name">
                                                <div class="object-cover h-full flex items-center justify-center">
                                                    <i :class="fileIcons[ resource.extension ] || fileIcons.unknown" class="las text-8xl text-white"></i>
                                                </div>
                                            </template>
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
                    <div class="h-64 bg-gray-800 flex items-center justify-center" v-if="panelOpened">
                        <img v-if="isImage( selectedResource )" class="object-cover h-full" :src="selectedResource.sizes.thumb" :alt="selectedResource.name"/>
                        <template v-if="! isImage( selectedResource )" class="object-cover h-full" :alt="selectedResource.name">
                            <div class="object-cover h-full flex items-center justify-center">
                                <i :class="fileIcons[ selectedResource.extension ] || fileIcons.unknown" class="las text-8xl text-white"></i>
                            </div>
                        </template>
                    </div>
                    <div id="details" class="p-4 text-gray-700 text-sm" v-if="panelOpened">
                        <p class="flex flex-col mb-2">
                            <strong class="font-bold block">{{ __( 'File Name' ) }}: </strong>
                            <span class="p-2" @blur="submitChange( $event, selectedResource )" :contenteditable="selectedResource.fileEdit ? 'true' : 'false'" :class="selectedResource.fileEdit ? 'border-b border-input-edge bg-input-background' : ''" @click="selectedResource.fileEdit = true">{{ selectedResource.name }}</span>
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
            <div class="py-2 pr-2 flex ns-media-footer text-sm flex-shrink-0 justify-between">
                <div class="flex -mx-2 flex-shrink-0">
                    <div class="px-2" v-if="bulkSelect">
                        <div class="ns-button shadow rounded overflow-hidden info">
                            <button 
                                @click="cancelBulkSelect()" 
                                class="py-2 px-3">
                                <i class="las la-times"></i>
                                {{ __( 'Cancel' ) }}
                            </button>
                        </div>
                    </div>
                    <div class="px-2"  v-if="hasOneSelected && ! bulkSelect">
                        <div class="ns-button shadow rounded overflow-hidden info">
                            <button 
                                @click="bulkSelect = true"
                                class="py-2 px-3">
                                <i class="las la-check-circle"></i>    
                                {{ __( 'Bulk Select' ) }}                        
                            </button>
                        </div>
                    </div>
                    <div class="px-2"  v-if="hasOneSelected">
                        <div class="ns-button shadow rounded overflow-hidden warning">
                            <button 
                                @click="deleteSelected()"
                                class="py-2 px-3">
                                <i class="las la-trash"></i>
                                {{ __( 'Delete' ) }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="flex-shrink-0 -mx-2 flex">
                    <div class="px-2">
                        <div class="rounded shadow overflow-hidden flex text-sm">
                            <div class="ns-button" :class="response.current_page === 1 ? 'disabled cursor-not-allowed' : 'info'">
                                <button :disabled="response.current_page === 1" @click="loadGallery( response.current_page - 1 )" class="p-2">{{ __( 'Previous' ) }}</button>
                            </div>
                            <hr class="border-r mx-1 border-gray-700">
                            <div class="ns-button" :class="response.current_page === response.last_page ? 'disabled cursor-not-allowed' : 'info'">
                                <button :disabled="response.current_page === response.last_page" @click="loadGallery( response.current_page + 1 )" class="p-2">{{ __( 'Next' ) }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="px-2" v-if="isPopup && hasOneSelected">
                        <div class="ns-button info">
                            <button class="rounded shadow p-2 text-sm" @click="useSelectedEntries()">{{ __( 'Use Selected' ) }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>