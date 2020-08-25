<template>
    <div id="permission-wrapper">
        <div class="rounded shadow bg-white">
            <table>
                <thead>
                    <tr>
                        <td>A</td>
                        <td>B</td>
                        <td>C</td>
                        <td>D</td>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</template>
<script>
import { forkJoin } from "rxjs";
import { nsHttpClient } from "../../bootstrap";
export default {
    name: 'ns-permissions',
    mounted() {
        
    },
    methods: {
        loadPermissionsAndRoles() {
            forkJoin(
                nsHttpClient.get( '/api/nexopos/v4/users/roles' ),
                nsHttpClient.get( '/api/nexopos/v4/users/permissions' ),
            ).subscribe( result => {
                
            })
        }
    }
}
</script>