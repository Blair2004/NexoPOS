<template>
    <div class="picker mb-2">
        <label v-if="field" class="block leading-5 font-medium text-primary">{{ field.label }}</label>
        <div class="ns-button">
            <button @click="visible = !visible" :class="field ? 'mt-1 border' : 'shadow'" class="rounded cursor-pointer w-full px-1 py-1 flex items-center text-primary">
                <i class="las la-clock text-2xl"></i>
                <span class="mx-1 text-sm" v-if="currentDay && field">
                    <span v-if="field.value !== null">{{ currentDay.format( 'YYYY/MM/DD HH:mm' ) }}</span>
                    <span v-if="field.value === null">N/A</span>
                </span>
                <span class="mx-1 text-sm" v-if="currentDay && date">
                    <span v-if="date !== null">{{ currentDay.format( 'YYYY/MM/DD HH:mm' ) }}</span>
                    <span v-if="date === null">N/A</span>
                </span>
            </button>
        </div>
        <p class="text-sm text-secondary py-1" v-if="field">{{ field.description }}</p>
        <div class="relative z-10 h-0 w-0" v-if="visible">
            <div :class="field ? '-mt-4' : 'mt-2'" class="absolute w-72 shadow-xl rounded ns-box anim-duration-300 zoom-in-entrance flex flex-col">
                <div class="flex-auto" v-if="currentView === 'years'">
                    <div class="p-2 flex items-center">
                        <div>
                            <button @click="subMonth()" class="w-8 h-8 border border-numpad-edge outline-none text-numpad-text hover:bg-numpad-hover rounded"><i class="las la-angle-left"></i></button>
                        </div>
                        <div class="flex flex-auto font-semibold text-primary justify-center">
                            <span class="mr-2 cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'months' )">{{ currentDay.format( 'MMM' ) }}</span>
                            <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                        </div>
                        <div>
                            <button @click="addMonth()" class="w-8 h-8 border border-numpad-edge outline-none text-numpad-text hover:bg-numpad-hover rounded"><i class="las la-angle-right"></i></button>
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
                <div class="flex-auto" v-if="currentView === 'months'">
                    <div class="p-2 flex items-center">
                        <div>
                            <button @click="subMonth()" class="w-8 h-8 border ns-inset-button outline-none ns-inset-button rounded"><i class="las la-angle-left"></i></button>
                        </div>
                        <div class="flex flex-auto font-semibold text-primary justify-center">
                            <span class="mr-2 border-b border-info-secondary border-dashed">{{ currentDay.format( 'MMM' ) }}</span>
                            <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                        </div>
                        <div>
                            <button @click="addMonth()" class="w-8 h-8 border ns-inset-button outline-none ns-inset-button rounded"><i class="las la-angle-right"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-flow-row grid-cols-3 grid-rows-1 gap-0 text-primary">
                        <div :key="_index" v-for="( monthIndex, _index ) in months" class="h-8 flex justify-center items-center text-sm">
                            <template class="h-full w-full">
                                <div :class="momentCopy.month( monthIndex ).format( 'MM' ) === currentDay.format( 'MM' ) ? 'bg-info-secondary text-white border border-info-secondary' : 'hover:bg-numpad-hover border border-numpad-edge'" class="h-full w-full flex items-center justify-center cursor-pointer" @click="setMonth( monthIndex )">
                                    {{ momentCopy.format( 'MMM' ) }}
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex-auto" v-if="currentView === 'days'">
                    <div class="p-2 flex items-center">
                        <div>
                            <button @click="subMonth()" class="w-8 h-8 border outline-none ns-inset-button rounded"><i class="las la-angle-left"></i></button>
                        </div>
                        <div class="flex flex-auto font-semibold text-primary justify-center">
                            <span class="mr-2 cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'months' )">{{ currentDay.format( 'MMM' ) }}</span>
                            <span class="cursor-pointer border-b border-info-secondary border-dashed" @click="toggleView( 'years' )">{{ currentDay.format( 'YYYY' ) }}</span>
                        </div>
                        <div>
                            <button @click="addMonth()" class="w-8 h-8 border outline-none ns-inset-button rounded"><i class="las la-angle-right"></i></button>
                        </div>
                    </div>
                    <div class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-primary">
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Sun' ) }}</div>
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Mon' ) }}</div>
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Tue' ) }}</div>
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Wed' ) }}</div>
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Thr' ) }}</div>
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Fri' ) }}</div>
                        <div class="border border-numpad-edge h-8 flex justify-center items-center text-sm">{{ __( 'Sat' ) }}</div>
                    </div>
                    <div v-for="( week, index ) of calendar" :key="index" class="grid grid-flow-row grid-cols-7 grid-rows-1 gap-0 text-primary">
                        <div :key="_index" v-for="( dayOfWeek, _index ) in daysOfWeek" class="h-8 flex justify-center items-center text-sm">
                            <template v-for="(day,_dayIndex) of week" class="h-full w-full">
                                <div :key="_dayIndex" v-if="day.dayOfWeek === dayOfWeek" :class="day.date.format( 'DD' ) === currentDay.format( 'DD' ) ? 'text-white border border-info-secondary bg-info-secondary' : 'hover:bg-numpad-hover border border-numpad-edge'" class="h-full w-full flex items-center justify-center cursor-pointer" @click="selectDate( day.date )">
                                    {{ day.date.format( 'DD' ) }}
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
                                    <button @click="erase()" class="border ns-inset-button error hover:text-white rounded w-8 h-8 flex items-center justify-center">
                                        <i class="las la-trash"></i>
                                    </button>
                                </div>
                                <div class="px-1" v-if="currentView !== 'days'">
                                    <button @click="toggleView( 'days' )" class="border ns-inset-button error hover:text-white rounded w-8 h-8 flex items-center justify-center">
                                        <i class="las la-sign-out-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="px-2">
                            <div class="rounded flex">
                                <input placeholder="HH" ref="hours" @change="detectHoursChange( $event )" class="w-12 p-1 text-center border border-numpad-edge bg-input-disabled text-primary active:border-numpad-edge" v-model="hours" type="number">
                                <span class="mx-1">:</span>
                                <input placeholder="mm" ref="minutes" @change="detectMinuteChange( $event )" class="w-12 p-1 text-center border border-numpad-edge bg-input-disabled text-primary active:border-numpad-edge" v-model="minutes" type="number">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import moment from "moment";
import { __ } from "@/libraries/lang";
export default {
    name: 'ns-date-time-picker',
    props: [ 'field', 'date' ],
    data() {
        return {
            visible: false,
            hours: 0,
            minutes: 0,
            currentView: 'days',
            currentDay: undefined,
            moment,
            months: ( new Array(11) ).fill('').map( ( _, i ) => i ),
            daysOfWeek: (new Array(7)).fill('').map( ( _, i ) => i ),
            calendar: [
                [], // first week
            ],
        }
    },
    computed: {
        momentCopy() {
            return moment();
        }
    },
    watch: {
        visible() {
            if ( this.visible ) {
                setTimeout( () => {
                    this.$refs[ 'hours' ].addEventListener( 'focus', function() {
                        this.select();
                    });
                    this.$refs[ 'minutes' ].addEventListener( 'focus', function() {
                        this.select();
                    });
                }, 100 );
            }
        }
    },
    mounted() {
        document.addEventListener( 'mousedown', ( e ) => this.checkClickedItem( e ) );

        if ( this.field ) {
            this.currentDay     =   [ undefined, '', null ].includes( this.field.value ) ? moment() : moment( this.field.value );
        } else {
            this.currentDay     =   [ undefined, '', null ].includes( this.date ) ? moment() : moment( this.date );
        }

        console.log( this.date );

        this.hours      =   this.currentDay.format( 'HH' );
        this.minutes    =   this.currentDay.format( 'mm' );
        this.build();        
    },
    methods: {
        __,
        erase() {
            this.selectDate();
        },

        setYear( field ) {
            if ( parseInt( field.srcElement.value ) > 0 && parseInt( field.srcElement.value ) < 9999 ) {
                this.currentDay.year( field.srcElement.value );
                this.updateDateTime();
            }
        },
        subYear(){
            if ( parseFloat( this.currentDay.format( 'YYYY' ) ) > 0 ) {
                this.currentDay.subtract( 1, 'year' );
                this.updateDateTime();
            }
        },
        addYear(){
            this.currentDay.add( 1, 'year' );
            this.updateDateTime();
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
            this.updateDateTime();
        },
        detectHoursChange() {
            if ( parseFloat( this.hours ) < 0 ) {
                this.hours  =   0;
            }
            if ( parseFloat( this.hours ) > 23 ) {
                this.hours  =   23;
            }
            this.updateDateTime();
        },
        detectMinuteChange() {
            if ( parseFloat( this.minutes ) < 0 ) {
                this.minutes  =   0;
            }
            if ( parseFloat( this.minutes ) > 59 ) {
                this.minutes  =   59;
            }
            this.updateDateTime();
        },
        updateDateTime() {
            this.currentDay.hours( this.hours );
            this.currentDay.minutes( this.minutes );
            this.build();
            this.selectDate( this.currentDay );
        },
        checkClickedItem( event ) {
            let clickChildrens;

            clickChildrens        =   this.$el.contains( event.srcElement );
            
            if ( ! clickChildrens && this.visible ) {
                this.visible    =   false;
            }
        },
        selectDate( date ) {
            if ( ! [ undefined ].includes( date ) ) {
                this.currentDay     =   date;
                this.currentDay.hours( this.hours );
                this.currentDay.minutes( this.minutes );

                if ( this.field ) {
                    this.field.value    =   this.currentDay.format( 'YYYY/MM/DD HH:mm' );
                }

                this.$emit( 'change', this.field ? this.field : this.currentDay );
            } else {
                if ( this.field ) {
                    this.field.value    =   null;
                }
                
                this.$emit( 'change', null );
            }
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
                    isToday: currentCursor.isSame( moment.now(), 'day' )
                });

                if ( currentCursor.isSame( endOfMonth, 'day' ) ) {
                    break;
                }

                currentCursor.add( 1, 'day' );
            }
        }
    }
}
</script>