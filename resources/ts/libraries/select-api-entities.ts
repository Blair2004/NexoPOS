import nsSelectPopupVue from "~/popups/ns-select-popup.vue";
import { Popup } from "./popup";
import { joinArray } from "./join-array";

export async function selectApiEntities( resource: string, label: string, value: any ): Promise<{ names: string, values: number[]}> {
    return await new Promise( ( resolve, reject ) => {
        nsHttpClient.get( resource )
            .subscribe({
                next: async ( resources ) => {
                    try {
                        const result    =   <number[]>(await new Promise( ( resolve, reject ) => {
                            Popup.show( nsSelectPopupVue, {
                                label,
                                type: 'multiselect',
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

                        const names     =   resources
                            .filter( resource => result.includes( resource.id ) )
                            .map( resource => resource.name )

                        return resolve({
                            names: joinArray( names ),
                            values: result
                        })

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