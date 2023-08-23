<template>
    <div class="ns-search">
        <div class="input-group info border-2">
            <input type="text" v-model="searchText" class="p-2 w-full outline-none" :placeholder="placeholder || __( 'Search...' )" id="">
        </div>
        <div class="relative">
            <div class="w-full absolute shadow-lg">
                <ul class="ns-vertical-menu" v-if="results.length > 0 && searchText.length > 0">
                    <li class="border-b p-2 cursor-pointer" v-for="( result, index ) of results" @click="selectOption( result )" :key="index">{{ renderLabel( result, label ) }}</li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script>
import { __ } from '~/libraries/lang';
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
export default {
    name: 'ns-search',
    props: [ 'url', 'placeholder', 'value', 'label', 'method', 'searchArgument' ],
    data() {
        return {
            searchText: '',
            searchTimeout: null,
            results: [],
        }
    },
    methods: {
        __,
        selectOption( result ) {
            this.$emit( 'select', result );
            this.searchText     =   '';
            this.results        =   [];
        },
        renderLabel( object, label ) {
            /**
             * if we would like to render
             * more than one label from the entry
             * the label can therefore be an array of attributes
             */
            if ( typeof label === 'object' ) {
                return label.map( attribute => object[ attribute ] ).join( ' ' );
            } else {
                return object[ label ];
            }
        }
    },
    watch: {
        searchText() {
            clearTimeout( this.searchTimeout );
            this.searchTimeout  =   setTimeout( () => {
                if ( this.searchText.length > 0 ) {
                    nsHttpClient[ this.method || 'post' ]( this.url, { [ this.searchArgument || 'search' ] : this.searchText })
                        .subscribe({
                            next: results => {
                                this.results    =   results;
                            },
                            error: error => {
                                nsSnackBar.error( error.message || __( 'An unexpected error occurred.' ) ).subscribe();
                            }
                        })
                }
            }, 1000 );
        }
    },
    mounted() {
        // ...
    }
}
</script>
