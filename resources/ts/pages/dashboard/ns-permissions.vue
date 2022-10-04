<template>
    <div id="permission-wrapper">
        <div class="rounded shadow ns-box flex">
            <div id="permissions" class="w- bg-gray-800 flex-shrink-0">
                <div class="h-24 py-4 px-2 border-b border-gray-700 text-gray-100 flex justify-between items-center">
                    <span v-if="! toggled">{{ __( 'Permissions' ) }}</span>
                    <div>
                        <button @click="toggled = ! toggled" class="rounded-full bg-white text-gray-700 h-6 w-6 flex items-center justify-center" v-if="! toggled">
                            <i class="las la-expand"></i>
                        </button>
                        <button @click="toggled = ! toggled" class="rounded-full bg-white text-gray-700 h-6 w-6 flex items-center justify-center" v-if="toggled">
                            <i class="las la-compress"></i>
                        </button>
                    </div>
                </div>
                <div :key="permission.id" v-for="permission of permissions" :class="toggled ? 'w-24' : 'w-54'" class="p-2 border-b border-gray-700 text-gray-100">
                    <a href="javascript:void(0)" :title="permission.namespace">
                        <span v-if="! toggled">{{ permission.name }}</span>
                        <span v-if="toggled">{{ permission.name | truncate(5) }}</span>
                    </a>
                </div>
            </div>
            <div class="flex flex-auto overflow-hidden">
                <div class="overflow-y-auto">
                    <div class="text-gray-700 flex">
                        <div v-for="role of roles" :key="role.id" class="h-24 py-4 px-2 w-56 items-center border-b justify-center flex role flex-shrink-0 border-r border-table-th-edge">
                            <p class="mx-1"><span>{{ role.name }}</span></p>
                            <span class="mx-1"><ns-checkbox @change="selectAllPermissions( role )" :field="role.field"></ns-checkbox></span>
                        </div>
                    </div>
                    <div :key="permission.id" v-for="permission of permissions" class="permission flex">
                        <div v-for="role of roles" :key="role.id" class="border-b border-table-th-edge w-56 flex-shrink-0 p-2 flex items-center justify-center border-r">
                            <ns-checkbox @change="submitPermissions( role, role.fields[ permission.namespace ] )" :field="role.fields[ permission.namespace ]"></ns-checkbox>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsTruncate } from '@/filters/truncate';
import { forkJoin } from "rxjs";
import { nsHttpClient, nsSnackBar } from "../../bootstrap";
import { __ } from '@/libraries/lang';
export default {
    name: 'ns-permissions',
    filters: [
        nsTruncate,
    ],
    data() {
        return {
            permissions: [],
            toggled: false,
            roles: [],
        }
    },
    mounted() {
        this.loadPermissionsAndRoles();
    },
    methods: {
        __,
        /**
         * before performing a bulk edit action for a specific role
         * we should first check if it's a system role and ask for confirmation
         * @param {object} role
         * @return void
         */
        selectAllPermissions( role ) {
            const roles   =   new Object;
            roles[ role.namespace ]                     =   new Object;

            if ( 
                ! role.locked ||
                ( role.locked && confirm( this.$slots[ 'bulk-edit-system-role' ] ? this.$slots[ 'bulk-edit-system-role' ][0].text : 'No message has been provided for "bulk-edit-system-role"' ) )
                
            ) {    
                for( let permission in role.fields ) {
                    role.fields[ permission ].value             =   role.field.value;
                    roles[ role.namespace ][ permission ]       =   role.field.value;
                }    

                nsHttpClient.put( '/api/nexopos/v4/users/roles', roles )
                    .subscribe( result => {
                        nsSnackBar.success( result.message, null, {
                            duration: 3000
                        }).subscribe();
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

            nsHttpClient.put( '/api/nexopos/v4/users/roles', roles )
                .subscribe( result => {
                    nsSnackBar.success( result.message, null, {
                        duration: 3000
                    }).subscribe();
                })
        },

        /**
         * Load Permission and roles
         * @return void
         */
        loadPermissionsAndRoles() {
            return forkJoin(
                nsHttpClient.get( '/api/nexopos/v4/users/roles' ),
                nsHttpClient.get( '/api/nexopos/v4/users/permissions' ),
            ).subscribe( result => {
                this.permissions    =   result[1];
                this.roles          =   result[0].map( role => {
                    let isChecked           =   false;
                    role.fields             =   {};
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