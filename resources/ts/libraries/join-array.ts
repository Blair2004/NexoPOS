import { __ } from "./lang";

export function joinArray(array, limit = 2 ) {
    // Check if the array is empty
    if (array.length === 0) {
        return "";
    }

    // Check if the array has only one element
    if (array.length === 1) {
        return array[0];
    }

    // Check if the array has more than five elements
    if ( array.length > limit ) {
        // Slice the first five elements and join them with a comma and a space
        let firstFive = array.slice(0, limit).join(", ");
        // Calculate the number of remaining elements
        let remaining = array.length - limit;
        // Append the number of remaining elements with a plus sign and the word "other"
        let rest = __( '+{count} other' ).replace( '{count}', remaining );
        // Return the joined string
        return `${firstFive}, ${rest}`;
    }

    // Otherwise, join the whole array with a comma and a space
    return array.join(", ");
}