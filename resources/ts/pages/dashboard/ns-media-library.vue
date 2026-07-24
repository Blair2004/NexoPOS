<script lang="ts">
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import popupCloser from '~/libraries/popup-closer';
import { __ } from '~/libraries/lang';
import nsPosConfirmPopupVue from '~/popups/ns-pos-confirm-popup.vue';
import { fileIcons } from '~/shared/file-icons';
import { Popup } from '~/libraries/popup';
import { nsAlertPopup } from '~/components/components';

declare const ns;
declare const nsHooks;
declare const window;

type MediaResource = {
    id: number;
    name: string;
    extension: string;
    slug: string;
    user?: {
        username?: string;
    };
    sizes: {
        original: string;
        thumb?: string;
    };
    created_at: string;
    updated_at: string;
    selected?: boolean;
    fileEdit?: boolean;
};

type UploadResource = {
    temporaryId: string;
    name: string;
    extension: string;
    progress: number;
    failed: boolean;
    error?: any;
};

export default {
    name: 'ns-media-library',
    props: [ 'popup' ],
    data() {
        return {
            searchFieldDebounce: null,
            searchField: '',
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
                first_page: 0,
            },
            resources: [] as MediaResource[],
            uploads: [] as UploadResource[],
            fileIcons,
            queryPage: 1,
            loading: false,
            loadingMore: false,
            isDragging: false,
            dragActivationTimeout: null,
            bulkSelect: false,
        };
    },
    mounted() {
        this.popupCloser();
        this.loadGallery( 1, true );
        window.addEventListener( 'paste', this.handlePasteUpload );
    },
    beforeUnmount() {
        window.removeEventListener( 'paste', this.handlePasteUpload );
        clearTimeout( this.searchFieldDebounce );
        this.clearDragActivation();
    },
    watch: {
        searchField() {
            clearTimeout( this.searchFieldDebounce );
            this.searchFieldDebounce = setTimeout( () => {
                this.loadGallery( 1, true );
            }, 500 );
        },
    },
    computed: {
        galleryItems() {
            return [ ...this.uploads, ...this.resources ];
        },
        selectedResources() {
            return this.resources.filter( resource => resource.selected );
        },
        hasOneSelected() {
            return this.selectedResources.length > 0;
        },
        selectedResource() {
            return this.selectedResources[0];
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
        },
        canLoadMore() {
            return this.response.next_page_url !== null && this.response.next_page_url !== '';
        },
    },
    methods: {
        popupCloser,
        __,

        clearSearch() {
            this.searchField = '';
        },

        triggerManualUpload() {
            this.$refs.files.click();
        },

        preventDefaults( event ) {
            event.preventDefault();
            event.stopPropagation();
        },

        isFileDrag( event ) {
            return Array.from( event.dataTransfer?.types || [] ).includes( 'Files' );
        },

        clearDragActivation() {
            if ( this.dragActivationTimeout ) {
                clearTimeout( this.dragActivationTimeout );
                this.dragActivationTimeout = null;
            }

            this.isDragging = false;
        },

        handleDragEnter( event ) {
            this.preventDefaults( event );

            if ( ! this.isFileDrag( event ) || this.dragActivationTimeout || this.isDragging ) {
                return;
            }

            this.dragActivationTimeout = setTimeout( () => {
                this.isDragging = true;
                this.dragActivationTimeout = null;
            }, 1000 );
        },

        handleDragLeave( event ) {
            this.preventDefaults( event );

            if ( event.currentTarget === event.target ) {
                this.clearDragActivation();
            }
        },

        handleDrop( event ) {
            this.preventDefaults( event );

            const canUpload = this.isDragging;

            this.clearDragActivation();

            if ( canUpload ) {
                this.processFiles( event.dataTransfer.files );
            }
        },

        processFiles( files ) {
            const arrayFiles = Array.from( files ) as File[];
            const valid = arrayFiles.filter( file => Object.values( window.ns.medias.mimes ).includes( file.type ) );
            const invalidFiles = arrayFiles.length - valid.length;

            if ( invalidFiles > 0 ) {
                nsSnackBar.error(
                    invalidFiles === 1
                        ? __( '1 file was rejected due to invalid file type.' )
                        : __( '%s files were rejected due to invalid file type.' ).replace( '%s', invalidFiles.toString() ),
                    __( 'OK' )
                );
            }

            if ( valid.length === 0 && arrayFiles.length > 0 ) {
                nsSnackBar.error( __( 'No valid files selected. Please select supported file types.' ), __( 'OK' ) );
                return;
            }

            valid.forEach( file => this.uploadFile( file ) );
        },

        uploadFile( file: File ) {
            const temporaryId = `${Date.now()}-${Math.random().toString( 36 ).slice( 2 )}`;
            const extension = file.name.split( '.' ).pop()?.toLowerCase() || '';
            const upload: UploadResource = {
                temporaryId,
                name: file.name,
                extension,
                progress: 0,
                failed: false,
            };

            this.uploads.unshift( upload );

            const formData = new FormData();
            formData.append( 'file', file );

            window.Axios.post( nsHooks.applyFilters( 'http-client-url', '/api/medias' ), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: progressEvent => {
                    if ( progressEvent.total ) {
                        upload.progress = Math.max( 1, Math.round( ( progressEvent.loaded * 100 ) / progressEvent.total ) );
                    }
                },
            } ).then( response => {
                const media = this.normalizeResource( response.data );
                const uploadIndex = this.uploads.findIndex( item => item.temporaryId === temporaryId );

                if ( uploadIndex !== -1 ) {
                    this.uploads.splice( uploadIndex, 1 );
                }

                this.resources = this.resources.filter( resource => resource.id !== media.id );
                this.resources.unshift( media );
                this.selectResource( media );
            } ).catch( error => {
                upload.failed = true;
                upload.progress = 100;
                upload.error = error.response?.data || error.response || error;
                nsSnackBar.error( upload.error?.message || __( 'Failed to upload file.' ), __( 'OK' ) );
            } );
        },

        openUploadError( upload: UploadResource ) {
            Popup.show( nsAlertPopup, {
                title: __( 'An error occurred' ),
                message: upload.error?.message || __( 'An unexpected error occurred.' ),
            } );
        },

        handlePasteUpload( event: ClipboardEvent ) {
            if ( ! event.clipboardData ) {
                return;
            }

            const files: File[] = [];

            Array.from( event.clipboardData.items ).forEach( item => {
                if ( item.kind === 'file' && item.type.startsWith( 'image/' ) ) {
                    const file = item.getAsFile();

                    if ( file ) {
                        files.push( file );
                    }
                }
            } );

            if ( files.length > 0 ) {
                this.processFiles( files );
            }
        },

        loadGallery( page = 1, replace = false ) {
            if ( this.loading || this.loadingMore ) {
                return;
            }

            this.queryPage = page;
            this.loading = replace;
            this.loadingMore = ! replace;

            nsHttpClient.get( `/api/medias?page=${page}&user_id=${this.user_id}${this.searchField.length > 0 ? `&search=${this.searchField}` : ''}` )
                .subscribe( {
                    next: result => {
                        result.data.forEach( resource => resource.selected = false );
                        this.response = result;

                        const incomingResources = result.data.map( resource => this.normalizeResource( resource ) );

                        if ( replace ) {
                            this.resources = incomingResources;
                            this.bulkSelect = false;
                        } else {
                            const knownIds = this.resources.map( resource => resource.id );
                            this.resources = [
                                ...this.resources,
                                ...incomingResources.filter( resource => ! knownIds.includes( resource.id ) ),
                            ];
                        }
                    },
                    error: error => {
                        nsSnackBar.error(
                            error.message || __( 'An error occurred while loading the media gallery.' ),
                            __( 'OK' )
                        );
                    },
                    complete: () => {
                        this.loading = false;
                        this.loadingMore = false;
                    },
                } );
        },

        normalizeResource( resource ): MediaResource {
            return {
                ...resource,
                selected: resource.selected || false,
                fileEdit: resource.fileEdit || false,
            };
        },

        onGalleryScroll( event ) {
            const element = event.target;
            const scrollDistance = element.scrollHeight - element.scrollTop - element.clientHeight;

            if ( scrollDistance < 360 && this.canLoadMore && ! this.loadingMore ) {
                this.loadGallery( this.response.current_page + 1 );
            }
        },

        submitChange( field, selectedResource: MediaResource ) {
            nsHttpClient.put( `/api/medias/${selectedResource.id}`, {
                name: field.srcElement.textContent,
            } ).subscribe( {
                next: result => {
                    selectedResource.fileEdit = false;
                    nsSnackBar.success( result.message, 'OK' );
                },
                error: error => {
                    selectedResource.fileEdit = false;
                    nsSnackBar.error( error.message || __( 'An unexpected error occurred.' ), 'OK' );
                },
            } );
        },

        selectResource( resource: MediaResource ) {
            if ( ! this.bulkSelect ) {
                this.resources.forEach( item => {
                    if ( item.id !== resource.id ) {
                        item.selected = false;
                    }
                } );
            }

            resource.fileEdit = false;
            resource.selected = ! resource.selected;
        },

        cancelBulkSelect() {
            this.bulkSelect = false;
            this.resources.forEach( resource => resource.selected = false );
        },

        deleteSelected() {
            Popup.show( nsPosConfirmPopupVue, {
                title: __( 'Confirm Your Action' ),
                message: __( 'You\'re about to delete selected resources. Would you like to proceed?' ),
                onAction: action => {
                    if ( action ) {
                        const ids = this.selectedResources.map( resource => resource.id );

                        nsHttpClient.post( '/api/medias/bulk-delete', { ids } )
                            .subscribe( {
                                next: result => {
                                    nsSnackBar.success( result.message );
                                    this.resources = this.resources.filter( resource => ! ids.includes( resource.id ) );
                                    this.bulkSelect = false;
                                },
                                error: error => {
                                    nsSnackBar.error( error.message );
                                },
                            } );
                    }
                },
            } );
        },

        useSelectedEntries() {
            this.popup.params.resolve( {
                event: 'use-selected',
                value: this.selectedResources,
            } );
            this.popup.close();
        },

        isImage( media ) {
            const imageExtensions = Object.keys( ns.medias.imageMimes );
            return imageExtensions.includes( media.extension );
        },

        copyUrl( resource: MediaResource ) {
            navigator.clipboard.writeText( resource.sizes.original );
            nsSnackBar.success( __( 'The URL has been copied.' ), __( 'OK' ) );
        },

        download( resource: MediaResource ) {
            window.open( resource.sizes.original, '_blank' );
        },
    },
};
</script>

<template>
    <div
        id="ns-media-library"
        class="ns-box flex flex-col overflow-hidden bg-box-background text-fontcolor"
        :class="isPopup ? 'w-[85.71vw] h-[95vh] shadow-xl' : 'm-4 h-full w-auto rounded-lg'"
        @dragenter="handleDragEnter"
        @dragleave="handleDragLeave"
        @dragover="preventDefaults"
        @drop="handleDrop">
        <input class="hidden" type="file" multiple ref="files" @change="processFiles( $event.currentTarget.files )">

        <div class="flex flex-shrink-0 items-center justify-between gap-3 border-b border-input-edge p-3">
            <div class="min-w-0">
                <h3 class="text-lg font-bold leading-6">{{ __( 'Library' ) }}</h3>
                <p class="text-xs text-soft-tertiary">{{ response.total }} {{ __( 'files' ) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="ns-button info">
                    <button class="rounded p-2 text-xs flex justify-center items-center" @click="triggerManualUpload" :title="__( 'Upload' )">
                        {{ __( 'Upload' ) }}
                        <i class="las la-upload text-lg"></i>
                    </button>
                </div>
                <ns-close-button v-if="isPopup" @click="popupInstance.close()"></ns-close-button>
            </div>
        </div>

        <div class="flex min-h-0 flex-auto overflow-hidden">
            <div
                class="relative flex min-w-0 flex-auto flex-col overflow-hidden"
                :class="isDragging ? 'ring-2 ring-info-primary' : ''">
                <div class="flex flex-shrink-0 flex-col gap-3 border-b border-input-edge p-2 md:flex-row md:items-center md:justify-between">
                    <div class="ns-input flex h-10 min-w-0 flex-auto overflow-hidden rounded border border-input-edge">
                        <input id="search" type="text" v-model="searchField" :placeholder="__( 'Search Medias' )" class="block h-full min-w-0 flex-auto px-3 text-sm">
                        <button v-if="searchField.length > 0" @click="clearSearch" class="flex h-full w-10 flex-shrink-0 items-center justify-center" :title="__( 'Cancel' )">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-auto overflow-y-auto p-3 ns-scrollbar" @scroll="onGalleryScroll">
                    <div v-if="galleryItems.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                        <div
                            v-for="item of galleryItems"
                            :key="'temporaryId' in item ? item.temporaryId : item.id"
                            class="group overflow-hidden rounded border border-input-edge bg-input-background">
                            <template v-if="'temporaryId' in item">
                                <div class="aspect-square bg-secondary/40 p-4">
                                    <div class="flex h-full flex-col items-center justify-center gap-3 text-center">
                                        <i :class="fileIcons[ item.extension ] || fileIcons.unknown" class="las text-5xl text-soft-tertiary"></i>
                                        <div class="h-2 w-full overflow-hidden rounded bg-box-elevation">
                                            <div class="h-full bg-info-primary" :style="{ width: `${item.progress}%` }"></div>
                                        </div>
                                        <button v-if="item.failed" @click="openUploadError( item )" class="text-xs text-error-primary">{{ __( 'See Error' ) }}</button>
                                        <span v-else class="text-xs text-soft-tertiary">{{ item.progress }}%</span>
                                    </div>
                                </div>
                                <div class="min-w-0 p-2">
                                    <p class="truncate text-xs font-semibold">{{ item.name }}</p>
                                </div>
                            </template>

                            <template v-else>
                                <button
                                    @click="selectResource( item )"
                                    class="relative flex aspect-square w-full items-center justify-center overflow-hidden bg-secondary/40"
                                    :class="item.selected ? 'ring-2 ring-info-primary' : ''">
                                    <img v-if="isImage( item )" class="h-full w-full object-cover" :src="item.sizes.thumb || item.sizes.original" :alt="item.name">
                                    <i v-else :class="fileIcons[ item.extension ] || fileIcons.unknown" class="las text-6xl text-white"></i>
                                    <span v-if="item.selected" class="absolute left-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-info-primary text-white">
                                        <i class="las la-check"></i>
                                    </span>
                                </button>
                                <div class="min-w-0 p-2">
                                    <p class="truncate text-xs font-semibold">{{ item.name }}.{{ item.extension }}</p>
                                    <p class="truncate text-[11px] text-soft-tertiary">{{ item.created_at }}</p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div v-if="loading" class="flex h-full items-center justify-center text-sm text-soft-tertiary">
                        {{ __( 'Loading media library...' ) }}
                    </div>

                    <div v-if="! loading && galleryItems.length === 0" class="flex h-full min-h-80 items-center justify-center text-center">
                        <div class="max-w-sm">
                            <i class="las la-cloud-upload-alt text-6xl text-soft-tertiary"></i>
                            <h3 class="mt-2 text-lg font-bold">{{ __( 'Drop files here to upload.' ) }}</h3>
                        </div>
                    </div>

                    <div v-if="loadingMore" class="flex items-center justify-center p-4 text-sm text-soft-tertiary">
                        {{ __( 'Loading more media...' ) }}
                    </div>
                </div>

                <div class="h-[3.5em] shrink-0 flex justify-between items-center px-2">
                    <div class="flex justify-between flex-shrink-0 items-center gap-2">
                        <ns-button v-if="hasOneSelected && ! bulkSelect" @click="bulkSelect = true" class="ns-button info rounded text-sm">
                            <i class="las la-check-circle"></i>
                            {{ __( 'Bulk Select' ) }}
                        </ns-button>
                        <ns-button v-if="bulkSelect" @click="cancelBulkSelect" class="ns-button info rounded text-sm">
                            <i class="las la-times"></i>
                            {{ __( 'Cancel' ) }}
                        </ns-button>
                        <ns-button v-if="hasOneSelected" @click="deleteSelected" class="ns-button warning rounded text-sm">
                            <i class="las la-trash"></i>
                            {{ __( 'Delete' ) }}
                        </ns-button>
                    </div>
                    <div>
                        <ns-button v-if="isPopup && hasOneSelected" class="ns-button info rounded text-sm" @click="useSelectedEntries">
                            {{ __( 'Use Selected' ) }}
                        </ns-button>
                    </div>
                </div>

                <div v-if="isDragging" class="pointer-events-none absolute inset-3 z-10 flex items-center justify-center rounded border-2 border-dashed border-info-primary bg-box-background/80">
                    <div class="text-center">
                        <i class="las la-cloud-upload-alt text-6xl text-info-primary"></i>
                        <h3 class="mt-2 text-xl font-bold">{{ __( 'Drop to upload' ) }}</h3>
                    </div>
                </div>
            </div>

            <aside class="hidden w-80 flex-shrink-0 border-l border-input-edge lg:flex lg:flex-col">
                <template v-if="panelOpened">
                    <div class="flex flex-shrink-0 items-center justify-between gap-2 border-b border-input-edge p-3">
                        <strong class="truncate text-sm">{{ selectedResource.name }}.{{ selectedResource.extension }}</strong>
                        <button class="flex h-8 w-8 items-center justify-center rounded hover:bg-input-background" @click="cancelBulkSelect" :title="__( 'Close' )">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                    <div class="flex-shrink-0 bg-secondary/40 p-3">
                        <div class="flex aspect-square items-center justify-center overflow-hidden rounded bg-box-elevation">
                            <img v-if="isImage( selectedResource )" class="h-full w-full object-cover" :src="selectedResource.sizes.thumb || selectedResource.sizes.original" :alt="selectedResource.name">
                            <i v-else :class="fileIcons[ selectedResource.extension ] || fileIcons.unknown" class="las text-8xl text-white"></i>
                        </div>
                    </div>
                    <div class="min-h-0 flex-auto overflow-y-auto p-4 text-sm ns-scrollbar">
                        <label class="mb-4 flex flex-col gap-1">
                            <span class="text-xs font-bold uppercase text-soft-tertiary">{{ __( 'File Name' ) }}</span>
                            <span class="rounded border border-input-edge bg-input-background p-2" @blur="submitChange( $event, selectedResource )" contenteditable="true">{{ selectedResource.name }}</span>
                        </label>
                        <dl class="grid gap-3 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-soft-tertiary">{{ __( 'File type' ) }}</dt>
                                <dd class="font-semibold uppercase">{{ selectedResource.extension }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-soft-tertiary">{{ __( 'Uploaded' ) }}</dt>
                                <dd class="text-right">{{ selectedResource.created_at }}</dd>
                            </div>
                            <div v-if="selectedResource.user" class="flex items-center justify-between gap-3">
                                <dt class="text-soft-tertiary">{{ __( 'By' ) }}</dt>
                                <dd class="text-right">{{ selectedResource.user.username }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="grid flex-shrink-0 grid-cols-2 gap-2 border-t border-input-edge p-3">
                        <div class="ns-button default flex">
                            <button class="rounded p-2 flex-auto text-sm" @click="copyUrl( selectedResource )">
                                <i class="las la-link"></i>
                                {{ __( 'Copy URL' ) }}
                            </button>
                        </div>
                        <div class="ns-button info flex">
                            <button class="ns-button info rounded p-2 flex-auto text-sm" @click="triggerManualUpload">
                                <i class="las la-sync"></i>
                                {{ __( 'Replace' ) }}
                            </button>
                        </div>
                        <div class="ns-button success flex">
                            <button class="ns-button info rounded p-2 flex-auto text-sm" @click="download( selectedResource )">
                                <i class="las la-download"></i>
                                {{ __( 'Download' ) }}
                            </button>
                        </div>
                        <div class="ns-button error flex">
                            <button class="ns-button warning rounded p-2 flex-auto text-sm" @click="deleteSelected">
                                <i class="las la-trash"></i>
                                {{ __( 'Delete' ) }}
                            </button>
                        </div>
                    </div>
                </template>
                <div v-else class="flex h-full items-center justify-center p-6 text-center text-sm text-soft-tertiary">
                    {{ __( 'Select a media item to view details.' ) }}
                </div>
            </aside>
        </div>
    </div>
</template>
