<template>
    <div id="permission-wrapper">
        <div class="rounded shadow bg-white flex">
            <div id="permissions" class="w-56 bg-gray-700 flex-shrink-0">
                <div class="py-4 px-2 border-b border-gray-800 text-gray-100">Permissions</div>
                <div :key="permission.id" v-for="permission of permissions" class="p-2 border-b border-gray-800 text-gray-100">{{ permission.name }}</div>
            </div>
            <div class="flex flex-auto overflow-hidden">
                <div class="overflow-y-auto">
                    <div class="border-b border-gray-200 text-gray-700 flex">
                        <div v-for="role of roles" :key="role.id" class="py-4 px-2 items-center justify-center flex role w-48 flex-shrink-0 border-r border-gray-200">{{ role.name }}</div>
                    </div>
                    <div :key="permission.id" v-for="permission of permissions" class="permission flex border-b border-gray-200">
                        <div v-for="role of roles" :key="role.id" class="w-48 flex-shrink-0 p-2 flex items-center justify-center border-r">
                            <ns-checkbox @change="submitPermissions( role )" :field="role.fields[ permission.namespace ]"></ns-checkbox>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { forkJoin } from "rxjs";
import { nsHttpClient, nsSnackBar } from "../../bootstrap";
export default {
    name: 'ns-permissions',
    data() {
        return {
            permissions: [],
            roles: [],
        }
    },
    mounted() {
        this.loadPermissionsAndRoles();
    },
    methods: {
        submitPermissions( role ) {
            const roles   =   new Object;

            roles[ role.namespace ]     =   new Object;
            for( let permission in role.fields ) {
                roles[ role.namespace ][ permission ]   =   role.fields[ permission ].value;
            }

            nsHttpClient.put( '/api/nexopos/v4/users/roles', roles )
                .subscribe( result => {
                    nsSnackBar.success( result.message, null, {
                        duration: 3000
                    }).subscribe();
                })
        },
        loadPermissionsAndRoles() {
            return forkJoin(
                nsHttpClient.get( '/api/nexopos/v4/users/roles' ),
                nsHttpClient.get( '/api/nexopos/v4/users/permissions' ),
            ).subscribe( result => {
                this.permissions    =   result[1];
                this.roles          =   result[0].map( role => {
                    let isChecked           =   false;
                    role.fields             =   {};
                    
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