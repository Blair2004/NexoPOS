<template>
    <div id="pagination" class="flex -mx-1">
        <template v-if="pagination.current_page">
            <a href="javascript:void(0)" @click="gotoPage( pagination.first_page )" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-inset-button info shadow">
                <i class="las la-angle-double-left"></i>
            </a>
            <template v-for="(_paginationPage, index) of getPagination">
                <a :key="index" v-if="page !== '...'" :class="page == _paginationPage ? 'active' : ''" @click="gotoPage( _paginationPage )" href="javascript:void(0)" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-inset-button info">{{ _paginationPage }}</a>
                <a :key="index" v-if="page === '...'" href="javascript:void(0)" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-inset-button">...</a>
            </template>
            <a href="javascript:void(0)" @click="gotoPage( pagination.last_page )" class="mx-1 flex items-center justify-center h-8 w-8 rounded-full ns-inset-button info shadow">
                <i class="las la-angle-double-right"></i>
            </a>
        </template>
    </div>
</template>
<script>
export default {
    name: 'ns-paginate',
    props: [ 'pagination' ],
    data: () => {
        return {
            page: 1,
            path: '',
        }
    },
    mounted(){
        this.path   =   this.pagination.path;
    },
    computed: {
        getPagination() {
            if ( this.pagination ) {
                return this.pageNumbers( this.pagination.last_page, this.pagination.current_page );
            }
            return [];
        },
    },
    methods: {
        gotoPage( page ) {
            this.page   =   page;
            this.$emit( 'load', `${this.path}?page=${this.page}` );
        },

        pageNumbers(count, current) {
            var shownPages = 3;
            var result = [];

            if ( current - 3 > 1 ) {
                result.push( 1, '...' );
            }

            for( let i = 1; i <= count; i++ ) {
                if ( current + 3 > i && current - 3 < i ) {
                    result.push(i);
                }
            }

            if ( current + 3 < count ) {
                result.push( '...', count );
            }
            
            return result.filter( f => f > 0 || typeof f === 'string' );
        },
    }
}
</script>