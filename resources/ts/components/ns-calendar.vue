<template>
    <div @click="handleCalendarClick()" class="flex-auto bg-input-background rounded-lg overflow-hidden">
        <div class="flex-auto" v-if="currentView === 'years'">
            <div class="p-2 flex items-center">
                <div>
                    <button @click="subMonth()" class="w-8 h-8 border-box-background border outline-none text-numpad-text hover:bg-numpad-hover rounded"><i class="las la-angle-left"></i></button>
                </div>
                <div class="flex flex-auto font-semibold text-primary justify-center">
                    <span class="mr-2 cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'months' )">{{ currentDay.format( 'MMM' ) }}</span>
                    <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                </div>
                <div>
                    <button @click="addMonth()" class="w-8 h-8 border-box-background border outline-none text-numpad-text hover:bg-numpad-hover rounded"><i class="las la-angle-right"></i></button>
                </div>
            </div>
            <div class="h-32 flex items-center justify-center text-primary">
                <div class="rounded input-group info border-2 flex w-2/3 overflow-hidden">
                    <button @click="subYear()" class="px-4 py-2">
                        <i class="las la-minus"></i>
                    </button>
                    <input type="text" ref="year" class="p-2 w-24 text-center outline-none" @change="setYear( $event )" :value="currentDay.format( 'YYYY' )">
                    <button @click="addYear()" class="px-4 py-2">
                        <i class="las la-plus"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="flex-auto border-b border-box-background" v-if="currentView === 'months'">
            <div class="p-2 flex items-center">
                <div>
                    <button @click="subYear()" class="w-8 h-8 ns-inset-button outline-none ns-inset-button border-box-background border rounded"><i class="las la-angle-left"></i></button>
                </div>
                <div class="flex flex-auto font-semibold text-primary justify-center">
                    <span class="mr-2 border-b border-info-secondary border-dashed">{{ currentDay.format( 'MMM' ) }}</span>
                    <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                </div>
                <div>
                    <button @click="addYear()" class="w-8 h-8 ns-inset-button outline-none ns-inset-button border-box-background border rounded"><i class="las la-angle-right"></i></button>
                </div>
            </div>
            <div class="grid grid-flow-row grid-cols-3 grid-rows-1 gap-0 text-primary divide-x divide-y">
                <div :key="_index" v-for="( monthIndex, _index ) in months" class="h-8 flex justify-center items-center text-sm border-box-background">
                    <div class="w-full h-full">
                        <div :class="momentCopy.month( monthIndex ).format( 'MM' ) === currentDay.format( 'MM' ) ? 'bg-info-secondary text-white' : 'hover:bg-numpad-hover'" class="h-full w-full border-box-background flex items-center justify-center cursor-pointer" @click="setMonth( monthIndex )">
                            {{ momentCopy.format( 'MMM' ) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="currentView === 'days'" class="border-b border-box-background">
            <div class="p-2 flex items-center">
                <div>
                    <button @click="subMonth()" class="w-8 h-8 bg-input-background border border-box-background rounded"><i class="las la-angle-left"></i></button>
                </div>
                <div class="flex flex-auto font-semibold text-primary justify-center">
                    <span class="mr-2 cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'months' )">{{ currentDay.format( 'MMM' ) }}</span>
                    <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                </div>
                <div>
                    <button @click="addMonth()" class="w-8 h-8 bg-input-background border border-box-background rounded"><i class="las la-angle-right"></i></button>
                </div>
            </div>
            <div class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-primary divide-x divide-y">
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Sun' ) }}</div>
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Mon' ) }}</div>
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Tue' ) }}</div>
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Wed' ) }}</div>
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Thr' ) }}</div>
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Fri' ) }}</div>
                <div class="h-8 flex justify-center items-center border-box-background text-sm">{{ __( 'Sat' ) }}</div>
            </div>
            <div v-for="(week, index) of calendar" :key="index" class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-primary divide-x divide-y">
                <div :key="_index" v-for="( dayOfWeek, _index) in daysOfWeek" class="h-8 flex justify-center items-center text-sm border-box-background">
                    <template v-for="(day,_dayIndex) of week" class="h-full w-full">
                        <div :key="_dayIndex" v-if="day.dayOfWeek === dayOfWeek" :class="getDayClass({ day, _dayIndex, dayOfWeek, _index, currentDay })" class="h-full w-full flex items-center justify-center cursor-pointer" @click="selectDate( day )">
                            <span v-if="! day.isDifferentMonth">{{ day.date.format( 'DD' ) }}</span>
                            <span v-if="day.isDifferentMonth" class="text-secondary">{{ day.date.format( 'DD' ) }}</span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        <div class="border-t border-numpad-edge w-full p-2">
            <div class="-mx-1 flex justify-between">
                <div class="px-1">
                    <div class="-mx-1 flex">
                        <!-- Displays only if it's a field -->
                        <div class="px-1" v-if="field">
                            <button @click="erase()" class="border ns-inset-button error hover:text-white rounded w-11 h-11 flex items-center justify-center">
                                <i class="las la-trash text-lg"></i>
                            </button>
                        </div>
                        <div class="px-1" v-if="currentView !== 'days'">
                            <button @click="toggleView( 'days' )" class="border ns-inset-button border-box-background error hover:text-white rounded w-11 h-11 flex items-center justify-center">
                                <i class="las la-sign-out-alt"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="rounded flex items-center justify-center p-1 border border-box-background">
                        <span class="px-3 text-primary">{{ __( 'Time' ) }}</span>
                        <input placeholder="HH" ref="hours" @change="detectHoursChange( $event )" class="w-12 p-1 text-center border border-numpad-edge bg-input-disabled text-primary active:border-numpad-edge" v-model="hours" type="number">
                        <span class="mx-1">:</span>
                        <input placeholder="mm" ref="minutes" @change="detectMinuteChange( $event )" class="w-12 p-1 text-center border border-numpad-edge bg-input-disabled text-primary active:border-numpad-edge" v-model="minutes" type="number">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import { __ } from '~/libraries/lang';

export default {
    name: "ns-calendar",
    props: [ 'date', 'field', 'visible' ],
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
            months: ( new Array(11) ).fill('').map( ( _, i ) => i ),
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

        this.currentDay     =   [ undefined, null ].includes( this.date ) ? moment() : moment( this.date );
        this.build();
    },
    methods: {
        __,
        handleCalendarClick(){
            this.clickedOnCalendar  =   true;
        },
        getDayClass({ day, _dayIndex, dayOfWeek, _index, currentDay }) {
            const classes =   [];
            
            classes.push( day.date.format( 'DD' ) === currentDay.format( 'DD' ) && day.date.format( 'MM' ) === currentDay.format( 'MM' ) ? 'bg-info-secondary text-primary border-info-secondary' : 'hover:bg-numpad-hover' );

            if ( _index === 0 ) {
                classes.push( 'border-t border-box-background' );
            }

            /**
             * This will highlight the days that
             * aren't part of the actual month.
             */
            if( day.isDifferentMonth ) {
                classes.push( 'bg-box-background' );
            }

            return classes.join( ' ' );
        },
        erase() {
            this.selectDate();
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
                this.currentDay     =   calendar.date;
                this.currentDay.hours( this.hours );
                this.currentDay.minutes( this.minutes );

                if ( this.field ) {
                    this.field.value    =   this.currentDay.format( 'YYYY/MM/DD HH:mm' );
                }

                this.$emit( 'set', this.field ? this.field : this.currentDay );
            } else {
                if ( this.field ) {
                    this.field.value    =   null;
                }
                
                this.$emit( 'set', null );
            }

            this.build();
        },
        subMonth() {
            this.currentDay.subtract( 1, 'month' );
            this.selectDate({ date: this.currentDay.clone() });
        },
        addMonth() {
            this.currentDay.add( 1, 'month' );
            this.selectDate({ date: this.currentDay.clone() });
        },
        resetCalendar() {
            this.calendar   =   [[]];
        },
        // !---
        
        build() {

            this.resetCalendar();

            const startOfMonth      =   this.currentDay.clone().startOf( 'month' );
            const currentCursor     =   this.currentDay.clone().startOf( 'month' );
            const endOfMonth        =   this.currentDay.clone().endOf( 'month' );
            
            while( true ) {
                if ( currentCursor.day() === 0 ) {
                    if ( this.calendar[0].length > 0 ) {
                        this.calendar.push([]);
                    }
                }

                let week    =   this.calendar.length - 1;

                this.calendar[ week ].push({
                    date: currentCursor.clone(),
                    dayOfWeek: currentCursor.day(),
                    isToday: currentCursor.isSame( moment.now(), 'day' ),
                    isDifferentMonth: false,
                    isNextMonth: false,
                    isPreviousMonth: false,
                });

                if ( currentCursor.isSame( endOfMonth, 'day' ) ) {
                    break;
                }

                currentCursor.add( 1, 'day' );
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