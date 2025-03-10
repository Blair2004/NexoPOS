<template>
    <div id="permission-wrapper">
        <div class="my-2">
            <input ref="search" v-model="searchText" type="text" :placeholder="__( 'Press &quot;/&quot; to search permissions' )" class="border-2 p-2 w-full outline-hidden bg-input-background border-secondary text-fontcolor">
        </div>
        <div class="ns-box mb-2 rounded">
            <template v-if="showRoleSelector">
                <div class="ns-box-body p-1 flex flex-wrap">
                    <div v-for="role of roles" :label="role.name" class="mr-4 mb-2 flex">
                        <label @click="role.visible = ! role.visible" class="mr-1">{{ role.name }} </label>
                        <ns-checkbox @change="role.visible = $event" :checked="role.visible"></ns-checkbox>
                    </div>
                </div>
                <div class="flex justify-end p-2 text-xs">
                    <div class="-mx-2 flex">
                        <div class="px-2">
                            <a href="javascript:void(0)" class="hover:underline" @click="selectAllRoles()">{{ __( 'Select All' ) }}</a>
                        </div>
                        <div class="px-2">
                            <a href="javascript:void(0)" class="hover:underline" @click="unselectAllRoles()">{{ __( 'Unselect All' ) }}</a>
                        </div>
                        <div class="px-2">
                            <a href="javascript:void(0)" @click="showRoleSelector = false" class="hover:underline ns-link">{{  __( 'Hide Roles' ) }}</a>
                        </div>
                    </div>
                </div>
            </template>
            <div v-else>
                <div class="p-2 text-xs">
                    <a href="javascript:void(0)" class="hover:underline ns-link" @click="showRoleSelector = true">{{ __( 'Show Roles' ) }}</a>
                </div>
            </div>
        </div>
        <div class="rounded shadow ns-box flex">
            <div id="permissions" class="w-54 bg-gray-800 flex-shrink-0">
                <div class="h-[50px] pl-[10px] border-b border-gray-700 text-fontcolor flex justify-between items-center">
                    <span>{{ __( 'Permissions' ) }}</span>
                </div>
                <div :key="permission.id" v-for="permission of filteredPermissions" class="w-54 h-[40px] flex items-center pl-[10px] border-b border-table-th-edge text-fontcolor">
                    <a @click="copyPermisson( permission.namespace )" href="javascript:void(0)" :title="permission.namespace">
                        <span class="text-xs">{{ permission.name }}</span>
                    </a>
                </div>
            </div>
            <div class="flex flex-auto overflow-hidden">
                <div class="overflow-y-auto">
                    <div class="text-gray-700 flex h-[50px]">
                        <div v-for="role of visibleRoles" :key="role.id" class="w-32 shrink-0 items-center border-b justify-center flex flex-col role text-xs border-r border-table-th-edge text-fontcolor">
                            <p class="mx-1 text-center"><span>{{ role.name }}</span></p>
                            <span class="mx-1"><ns-checkbox @change="selectAllPermissions( role )" :field="role.field"></ns-checkbox></span>
                        </div>
                    </div>
                    <div :key="permission.id" v-for="permission of filteredPermissions" class="permission flex h-[40px]">
                        <div v-for="role of visibleRoles" :key="role.id" class="border-b border-table-th-edge shrink-0 w-32 text-xs flex items-center justify-center border-r">
                            <ns-checkbox @change="submitPermissions( role, role.fields[ permission.namespace ] )" :field="role.fields[ permission.namespace ]"></ns-checkbox>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsTruncate } from '~/filters/truncate';
import { forkJoin } from "rxjs";
import { nsHttpClient, nsSnackBar } from "~/bootstrap";
import { __ } from '~/libraries/lang';
export default {
    name: 'ns-permissions',
    filters: [
        nsTruncate,
    ],
    data() {
        return {
            permissions: [],
            roles: [],
            searchText: '',
            showRoleSelector: false,
        }
    },
    computed: {
        filteredPermissions() {
            if ( this.searchText.length !== 0 ) {
                return this.permissions.filter( permission => {
                    const expression       =   new RegExp( this.searchText, 'i' );
                    return expression.test( permission.name ) || expression.test( permission.namespace );
                });
            }

            return this.permissions;
        },
        visibleRoles() {
            return this.roles.filter( role => role.visible );
        }
    },
    mounted() {
        this.loadPermissionsAndRoles();
        nsHotPress
            .create( 'ns-permissions' )
            .whenPressed( 'shift+/', ( event ) => {
                this.searchText     =   '';
                setTimeout( () => {
                    this.$refs[ 'search' ].focus();
                }, 5 );
            })
            .whenPressed( '/', ( event ) => {
                this.searchText     =   '';
                setTimeout( () => {
                    this.$refs[ 'search' ].focus();
                }, 5 );
            });
    },
    methods: {
        __,

        copyPermisson(text) {
            navigator.clipboard.writeText(text).then(function() {
                nsSnackBar.success( __( 'Copied to clipboard' ), null, {
                    duration: 3000
                });
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        },

        /**
         * before performing a bulk edit action for a specific role
         * we should first check if it's a system role and ask for confirmation
         * @param {object} role
         * @return void
         */
        async selectAllPermissions( role ) {
            const roles   =   new Object;
            roles[ role.namespace ]                     =   new Object;

            let confirmed   =   false;

            /**
             * If we're attempting to edit a system role
             * we should warn the user about that.
             */
            if ( role.locked ) {
                confirmed   =   await new Promise( ( resolve, reject) => {
                    Popup.show( nsConfirmPopup, { 
                        title: __( 'Confirm Your Action' ), 
                        message: __( 'Would you like to bulk edit a system role ?' ),
                        onAction: action => {
                            if ( action ) {
                                return resolve( true );
                            }
                            return resolve( false );
                        }
                    })
                });
            }

            if ( 
                ! role.locked ||
                ( role.locked && confirmed )
            ) {    
                const editedRoles   =   this.filterObjectByKeys( role.fields, this.filteredPermissions.map( permission => permission.namespace ) );

                for( let permission in editedRoles ) {
                    role.fields[ permission ].value             =   role.field.value;
                    roles[ role.namespace ][ permission ]       =   role.field.value;
                }    

                /**
                 * This will only update the currently
                 * filtered permissions.
                 */
                const filtredRoles    =   this.arrayToObject( this.filteredPermissions, 'namespace', ( permission ) => {
                    return roles[ role.namespace ][ permission.namespace ];
                });

                nsHttpClient.put( '/api/users/roles', roles )
                    .subscribe( result => {
                        nsSnackBar.success( result.message, null, {
                            duration: 3000
                        });
                    });
            } else {
                /**
                 * if there is no confirmation, let's 
                 * revert the preview value. Since it's supposed to be a boolean
                 * the preview value is the opposite (!)
                 */
                role.field.value    =   ! role.field.value;
            }
        },

        filterObjectByKeys( object, keys ) {
            return Object.fromEntries(
                Object.entries(object).filter(([key]) => keys.includes(key))
            );
        },

        /**
         * Maps an array to convert it into an object
         * @param {collect} array 
         * @param {string} key 
         * @param {any} value 
         */
        arrayToObject( array, key, valueCallback ) {
            return Object.assign(
                {}, 
                ...array.map(item => {
                    return {
                        [item[ key ]]: valueCallback( item )
                    };
                })
            );
        },

        /**
         * Submit role and permissions change
         * @param {Role} role
         * @param {Permission} permission
         * @return void
         */
        submitPermissions( role, permission ) {
            const roles   =   new Object;

            roles[ role.namespace ]                     =   new Object;
            roles[ role.namespace ][ permission.name ]  =   permission.value;

            nsHttpClient.put( '/api/users/roles', roles )
                .subscribe( result => {
                    nsSnackBar.success( result.message, null, {
                        duration: 3000
                    });
                })
        },

        /**
         * Select all roles
         * @return void
         */
        selectAllRoles() {
            this.roles.forEach( role => {
                role.visible     =   true;
            });
        },

        /**
         * Unselect all roles
         * @return void
         */
        unselectAllRoles() {
            this.roles.forEach( role => {
                role.visible     =   false;
            });
        },

        /**
         * Load Permission and roles
         * @return void
         */
        loadPermissionsAndRoles() {
            return forkJoin([
                nsHttpClient.get( '/api/users/roles' ),
                nsHttpClient.get( '/api/users/permissions' ),
            ]).subscribe( result => {
                this.permissions    =   result[1];
                this.roles          =   result[0].map( role => {
                    let isChecked           =   false;
                    role.fields             =   {};
                    role.visible            =   true;
                    role.field              =   {
                        type: 'checkbox',
                        name: role.namespace,
                        value: false
                    }
                    this.permissions.forEach( permission => {
                        role.fields[ permission.namespace ]     =   {
                            type: 'checkbox',
                            value: role.permissions
                                .filter( role_permission => role_permission.namespace === permission.namespace )
                                .length > 0,
                            name: permission.namespace,
                            label: null,
                        };
                    });

                    return role;
                });
            });
        }
    }
}
</script>