// import NsPosPermissionsPopup from "~/pages/dashboard/pos/ns-pos-permissions-popup.vue";
import NsPosPermissionsPopup from "~/popups/ns-pos-permissions-popup.vue";
NsPosPermissionsPopup

declare const POS, nsHttpClient, Popup;

export default class ActionPermissions {
    static async canProceed( permission )
    {
        return new Promise( ( resolve, reject ) => {
            const options = POS.options.getValue();
            const actionPermissionEnabled = options.ns_pos_action_permission_enabled === 'yes';

            if ( actionPermissionEnabled ) {
                const restrictedPermissions = options.ns_pos_action_permission_restricted_features;
                const permissionDuration = options.ns_pos_action_permission_duration;

                if ( restrictedPermissions.includes( permission ) ) {
                    nsHttpClient.post( `/api/users/check-permission/`, { permission })
                        .subscribe({
                            next: ( response ) => {
                                resolve( true );
                            },
                            error: ( error ) => {
                                if ( error.type && [ 'permission_denied', 'permission_pending' ].includes( error.type ) ) {
                                    Popup.show( NsPosPermissionsPopup, {
                                        featureName: 'foo',
                                        permission: permission,
                                        access_id: error.data.access.id,
                                        resolve,
                                        reject,
                                    } )
                                }
                            },
                        })
                }
            }
        })
    }
}