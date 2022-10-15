/**
 * describe what stock is currently in
 * use for the product that are added
 * on the card. Should update when a product 
 * is either added or removed.
 */
export class POSVirtualStock {  
    // product id
    [ key: number ] : {
        // unit id
        [ key: number ]: number // quantity
    }
}