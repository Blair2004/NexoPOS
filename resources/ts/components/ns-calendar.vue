<template>
    <div @click="handleCalendarClick()" class="flex bg-box-background flex-col rounded-lg overflow-hidden">
        <div class="flex-auto" v-if="currentView === 'years'">
            <div class="p-2 flex items-center">
                <div>
                    <button @click="subMonth()" class="w-8 h-8 ns-inset-button border outline-none text-numpad-text rounded"><i class="las la-angle-left"></i></button>
                </div>
                <div class="flex flex-auto font-semibold text-primary justify-center">
                    <span class="mr-2 cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'months' )">{{ currentDay.format( 'MMM' ) }}</span>
                    <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                </div>
                <div>
                    <button @click="addMonth()" class="w-8 h-8 ns-inset-button border outline-none text-numpad-text rounded"><i class="las la-angle-right"></i></button>
                </div>
            </div>
            <div class="h-32 flex grow-0 items-center justify-center text-primary p-4">
                <div class="rounded input-group info border-2 flex overflow-hidden">
                    <button @click="subYear()" class="px-2 py-2">
                        <i class="las la-minus"></i>
                    </button>
                    <div class="w-24 flex grow-0">
                        <input type="text" ref="year" class="p-2 flex-auto w-full text-center outline-none" @change="setYear( $event )" :value="currentDay.format( 'YYYY' )">
                    </div>
                    <button @click="addYear()" class="px-2 py-2">
                        <i class="las la-plus"></i>
                    </button>
                </div>
            </div>            
            <div class="p-2">
                <button @click="toggleView( 'days' )" class="p-2 w-full ns-inset-button border text-sm error hover:text-white rounded flex items-center justify-center">
                    {{ __( 'Return To Calendar' ) }}
                </button>
            </div>
        </div>
        <div class="flex-auto border-b border-box-background" v-if="currentView === 'months'">
            <div class="p-2 flex items-center">
                <div>
                    <button @click="subYear()" class="w-8 h-8 ns-inset-button outline-none border rounded"><i class="las la-angle-left"></i></button>
                </div>
                <div class="flex flex-auto font-semibold text-primary justify-center">
                    <span class="mr-2 border-b border-info-secondary border-dashed">{{ currentDay.format( 'MMM' ) }}</span>
                    <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                </div>
                <div>
                    <button @click="addYear()" class="w-8 h-8 ns-inset-button outline-none border rounded"><i class="las la-angle-right"></i></button>
                </div>
            </div>
            <div class="grid grid-flow-row grid-cols-3 grid-rows-1 gap-0 text-primary divide-x divide-y border-b border-box-background">
                <div :key="_index" v-for="( monthIndex, _index ) in months" class="h-8 flex justify-center items-center text-sm border-box-background">
                    <div class="w-full h-full">
                        <div :class="momentCopy.month( monthIndex ).format( 'MM' ) === currentDay.format( 'MM' ) ? 'bg-info-secondary text-white' : 'hover:bg-numpad-hover'" class="h-full w-full border-box-background flex items-center justify-center cursor-pointer" @click="setMonth( monthIndex )">
                            {{ momentCopy.format( 'MMM' ) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-2">
                <button @click="toggleView( 'days' )" class="p-2 w-full ns-inset-button border text-sm error rounded flex items-center justify-center">
                    {{ __( 'Return To Calendar' ) }}
                </button>
            </div>
        </div>
        <div v-if="currentView === 'days'">
            <div class="p-2 flex items-center">
                <div>
                    <button @click="subMonth()" class="w-8 h-8 ns-inset-button border rounded"><i class="las la-angle-left"></i></button>
                </div>
                <div class="flex flex-auto font-semibold text-primary justify-center">
                    <span class="mr-2 cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'months' )">{{ currentDay.format( 'MMM' ) }}</span>
                    <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                </div>
                <div>
                    <button @click="addMonth()" class="w-8 h-8 ns-inset-button border rounded"><i class="las la-angle-right"></i></button>
                </div>
            </div>
            <div class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-primary divide-x divide-y">
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Sun' ) }}</div>
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Mon' ) }}</div>
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Tue' ) }}</div>
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Wed' ) }}</div>
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Thr' ) }}</div>
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Fri' ) }}</div>
                <div class="md:h-10 h-8 flex justify-center items-center border-tab-table-th text-sm">{{ __( 'Sat' ) }}</div>
            </div>
            <div v-for="(week, index) of calendar" :key="index" class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-primary divide-x divide-y">
                <div :key="_index" v-for="( dayOfWeek, _index) in daysOfWeek" class="md:h-10 h-8 flex justify-center items-center text-sm border-tab-table-th">
                    <template v-for="(day,_dayIndex) of week" class="h-full w-full">
                        <div :key="_dayIndex" v-if="day.dayOfWeek === dayOfWeek" :class="getDayClass({ day, _dayIndex, dayOfWeek, _index, currentDay })" class="h-full w-full flex items-center justify-center cursor-pointer" @click="selectDate( day )">
                            <span v-if="! day.isDifferentMonth">{{ day.date.format( 'DD' ) }}</span>
                            <span v-if="day.isDifferentMonth" class="text-secondary">{{ day.date.format( 'DD' ) }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div class="border-t border-tab-table-th w-full p-2">
            <div class="-mx-1 flex justify-between">
                <div class="px-1">
                    <div class="-mx-1 flex">
                        <div class="px-1">
                            <button @click="erase()" class="border ns-inset-button text-sm error rounded md:w-10 w-8 md:h-10 h-8 flex items-center justify-center">
                                <i class="las la-trash text-lg"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="rounded border-tab-table-th-edge flex items-center justify-center p-1 border" v-if="currentView === 'days'">
                        <span class="pr-2 pl-1 text-primary">
                            <i class="las la-clock"></i>
                        </span>
                        <input placeholder="HH" ref="hours" @change="detectHoursChange( $event )" class="w-12 p-1 text-center border border-numpad-edge bg-input-background outline-none text-sm active:border-numpad-edge" v-model="hours" type="number">
                        <span class="mx-1">:</span>
                        <input placeholder="mm" ref="minutes" @change="detectMinuteChange( $event )" class="w-12 p-1 text-center border border-numpad-edge bg-input-background outline-none text-sm active:border-numpad-edge" v-model="minutes" type="number">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import { __ } from '~/libraries/lang';
import { nsSnackBar } from '~/bootstrap';

export default {
    name: "ns-calendar",
    props: [ 'date', 'field', 'visible', 'range', 'selected-range', 'side' ],
    data() {
        return {
            calendar: [
                [], // first week
            ],
            currentDay: moment(),
            daysOfWeek: (new Array(7)).fill('').map( ( _, i ) => i ),  
            hours: 0,
            minutes: 0,            
            currentView: 'days',
            clickedOnCalendar: false,

            moment,
            months: ( new Array(12) ).fill('').map( ( _, i ) => i ),
        }
    },
    computed: {
        momentCopy() {
            return moment();
        }
    },
    beforeUnmount() {
        document.removeEventListener( 'click', this.checkClickedItem );
    },
    mounted() {        
        document.addEventListener( 'click', this.checkClickedItem );

        this.currentDay     =   [ undefined, null, '' ].includes( this.date ) ? moment() : moment( this.date );
        this.hours          =   this.currentDay.hours();
        this.minutes        =   this.currentDay.minutes();
        this.build();
        this.toggleView( 'days' );
    },
    methods: {
        __,
        handleCalendarClick(){
            this.clickedOnCalendar  =   true;
        },
        getDayClass({ day, _dayIndex, dayOfWeek, _index, currentDay }) {
            const classes =   [];

            if ( ( moment( this.date ).isSame( day.date, 'day' ) || this.isRangeEdge( day ) ) && ! this.isInvalidRange() ) {
                classes.push( 'bg-info-secondary text-primary border-info-secondary text-white' );
            } else {
                classes.push( 'hover:bg-numpad-hover' );
            }

            if ( this.isInvalidRange() && this.isRangeEdge( day ) ) {
                classes.push( 'bg-error-secondary text-white' );
            }

            if ( _index === 0 ) {
                classes.push( 'border-t border-tab-table-th' );
            }

            /**
             * This will highlight the days that
             * aren't part of the actual month.
             */
            if ( this.isInRange( day ) && ! this.isRangeEdge( day ) ) {
                classes.push( 'bg-info-primary' );
            }
            else if( day.isDifferentMonth && ! this.isRangeEdge( day ) ) {
                classes.push( 'bg-tab-table-th' );
            }

            return classes.join( ' ' );
        },
        erase() {
            this.selectDate({ date: moment( ns.date.current ) });
        },

        isInRange( calendar ) {
            if ( this.range && this.range.length === 2 && this.range[0] && this.range[1] ) {
                return moment( calendar.date ).isSameOrAfter( this.range[0] ) && moment( calendar.date ).isSameOrBefore( this.range[1] );
            }

            return false;
        },

        isInvalidRange() {
            if ( this.selectedRange && this.selectedRange.endDate ) {
                return moment( this.selectedRange.startDate ).isAfter( moment( this.selectedRange.endDate ) ) ||
                    moment( this.selectedRange.endDate ).isBefore( moment( this.selectedRange.startDate ) );
            }

            return false;
        },

        isRangeEdge( calendar ) {
            if ( this.range && this.range.length === 2 && this.range[0] && this.range[1] ) {
                return moment( calendar.date ).isSame( this.range[0], 'day' ) || moment( calendar.date ).isSame( this.range[1], 'day' );
            }
            return false;
        },

        setYear( field ) {
            if ( parseInt( field.srcElement.value ) > 0 && parseInt( field.srcElement.value ) < 9999 ) {
                this.currentDay.year( field.srcElement.value );
                this.selectDate({ date: this.currentDay.clone() });
            }
        },
        subYear(){
            if ( parseFloat( this.currentDay.format( 'YYYY' ) ) > 0 ) {
                this.currentDay.subtract( 1, 'year' );
                this.selectDate({ date: this.currentDay.clone() });
            }
        },
        addYear(){
            this.currentDay.add( 1, 'year' );
            this.selectDate({ date: this.currentDay.clone() });
        },
        toggleView( currentView ) {
            this.currentView = currentView;  
            
            if ( this.currentView === 'years' ) {
                setTimeout( () => {
                    this.$refs.year.select();
                }, 100 );
            }

            if ( this.currentView === 'days' ) {
                setTimeout(() => {
                    this.$refs.hours.addEventListener( 'focus', function( e ) {
                        this.select();
                    });

                    this.$refs.minutes.addEventListener( 'focus', function( e ) {
                        this.select();
                    })
                }, 100);
            }
        },
        setMonth( index ) {
            this.currentDay.month( index );
            this.selectDate({ date: this.currentDay.clone() });
        },
        detectHoursChange() {
            if ( parseFloat( this.hours ) < 0 ) {
                this.hours  =   0;
            }
            if ( parseFloat( this.hours ) > 23 ) {
                this.hours  =   23;
            }
            this.selectDate({ date: this.currentDay.clone() });
        },
        detectMinuteChange() {
            if ( parseFloat( this.minutes ) < 0 ) {
                this.minutes  =   0;
            }
            if ( parseFloat( this.minutes ) > 59 ) {
                this.minutes  =   59;
            }
            this.selectDate({ date: this.currentDay.clone() });
        },
        checkClickedItem( event ) {
            /**
             * this short debounce will make sure
             * while switching view, the popup doesn't close
             */
            if ( this.$parent.$el.getAttribute( 'class' ).split( ' ' ).includes( 'picker' ) ) {
                let clickChildrens        =   this.$parent.$el.contains( event.srcElement );
                
                if ( ! clickChildrens && ! this.clickedOnCalendar && this.visible ) {
                    this.$emit( 'onClickOut', true );
                }

                /**
                 * This debounce will make sure
                 * the popup will be closed if  the next click
                 * is not on the calendar.
                 */
                setTimeout( () => {
                    this.clickedOnCalendar = false;
                }, 100 );
            }
        },
        selectDate( calendar ) {
            if ( ! [ undefined ].includes( calendar ) ) {
                /**
                 * if we're setting the left side and
                 * the date we want to set is beyond the right side date
                 * we'll stop and display an error.
                 */
                if ( 
                    this.side === 'left' && 
                    moment( this.selectedRange.endDate ).isValid() && 
                    calendar.date.isAfter( this.selectedRange.endDate ) ) {
                    nsSnackBar.error( __( 'The left range will be invalid.' ) ).subscribe();
                    return false;
                }

                /**
                 * if we're setting the right side and
                 * the date we want to set is beyond the left side date
                 * we'll stop and display an error.
                 */
                if ( 
                    this.side === 'right' && 
                    moment( this.selectedRange.startDate ).isValid() && 
                    calendar.date.isBefore( this.selectedRange.startDate ) ) {
                    nsSnackBar.error( __( 'The right range will be invalid.' ) ).subscribe();
                    return false;
                }

                this.currentDay     =   calendar.date;
                this.currentDay.hours( this.hours );
                this.currentDay.minutes( this.minutes );

                this.$emit( 'set', this.currentDay.format( 'YYYY-MM-DD HH:mm:ss') );
            } else {
                this.$emit( 'set', null );
            }

            this.build();
        },
        subMonth() {
            this.currentDay.subtract( 1, 'month' );
            this.build();
        },
        addMonth() {
            this.currentDay.add( 1, 'month' );
            this.build();
        },
        resetCalendar() {
            this.calendar   =   [[]];
        },
        
        build() {

            this.resetCalendar();

            const startOfMonth      =   this.currentDay.clone().startOf( 'month' );
            const currentDay     =   this.currentDay.clone().startOf( 'month' );
            const endOfMonth        =   this.currentDay.clone().endOf( 'month' );
            
            while( true ) {
                if ( currentDay.day() === 0 ) {
                    if ( this.calendar[0].length > 0 ) {
                        this.calendar.push([]);
                    }
                }

                let week    =   this.calendar.length - 1;

                this.calendar[ week ].push({
                    date: currentDay.clone(),
                    dayOfWeek: currentDay.day(),
                    isToday: currentDay.isSame( moment.now(), 'day' ),
                    isDifferentMonth: false,
                    isNextMonth: false,
                    isPreviousMonth: false,
                });

                if ( currentDay.isSame( endOfMonth, 'day' ) ) {
                    break;
                }

                currentDay.add( 1, 'day' );
            }

            /**
             * we'll now add the previous month days
             * and fill the missing boxes
             */
            if ( this.calendar[0].length < 7 ) {
                const diff              =   7 - this.calendar[0].length;
                const firstDayOfWeek    =  this.calendar[0][0].date.clone();
                const offMonthDays      =   [];
                
                for( let i = 0; i < diff ; i++ ) {
                    firstDayOfWeek.subtract( 1, 'day' );
                    offMonthDays.unshift({
                        date: firstDayOfWeek.clone(),
                        dayOfWeek: firstDayOfWeek.day(),
                        isToday: firstDayOfWeek.isSame( moment.now(), 'day' ),
                        isDifferentMonth: true,
                        isNextMonth: false,
                        isPreviousMonth: true,
                    })
                }

                this.calendar[0].unshift( ...offMonthDays );
            }

            /**
             * we'll now add the next month days
             * and fill the missing boxes
             */
            if ( this.calendar[ this.calendar.length - 1 ].length < 7 ) {
                const index             =   this.calendar.length - 1;
                const diff              =   7 - this.calendar[index].length;
                const lastDayOfWeek     =  this.calendar[index][ this.calendar[index].length - 1 ].date.clone();
                const offMonthDays      =   [];
                
                for( let i = 0; i < diff ; i++ ) {
                    lastDayOfWeek.add( 1, 'day' );
                    offMonthDays.push({
                        date: lastDayOfWeek.clone(),
                        dayOfWeek: lastDayOfWeek.day(),
                        isToday: lastDayOfWeek.isSame( moment.now(), 'day' ),
                        isDifferentMonth: true,
                        isNextMonth: true,
                        isPreviousMonth: false,
                    })
                }

                this.calendar[index].push( ...offMonthDays );
            }
        }
    }
}
</script>