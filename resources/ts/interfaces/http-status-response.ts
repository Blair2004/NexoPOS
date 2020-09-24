export interface HttpStatusResponse {
    type: string;
    message: string;
    data?: {
        [key:string] : any
    }
}