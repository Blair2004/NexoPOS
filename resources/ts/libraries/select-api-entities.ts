import nsSelectPopupVue from "~/popups/ns-select-popup.vue";
import { Popup } from "./popup";
import { joinArray } from "./join-array";

export async function selectApiEntities( resource: string, label: string, value: any, type: 'select' | 'multiselect' = 'multiselect' ): Promise<{ labels: string, values: number[]}> {
    return await new Promise( ( resolve, reject ) => {
        nsHttpClient.get( resource )
            .subscribe({
                next: async ( resources ) => {
                    try {
                        const result    =   <number[]|number>(await new Promise( ( resolve, reject ) => {
                            Popup.show( nsSelectPopupVue, {
                                label,
                                type,
                                options: resources.map( resource => {
                                    return {
                                        label: resource.name,
                                        value: resource.id
                                    }
                                }),
                                value,
                                resolve,
                                reject
                            })
                        }));

                        if ( type === 'multiselect' ) {
                            const labels     =   resources
                                .filter( resource => (<number[]>result).includes( resource.id ) )
                                .map( resource => resource.name )
    
                            return resolve({
                                labels: joinArray( labels ),
                                values: <number[]>result
                            })
                        } else {
                            const labels     =   resources
                                .filter( resource => +resource.id === +result )
                                .map( resource => resource.name )

                            return resolve({ labels, values: [<number>result] })
                        }

                    } catch( exception ) {
                        return reject( exception );
                    }
                },
                error: error => {
                    return reject( error );
                }
            })
    })
}