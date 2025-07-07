export interface HttpStatusResponse {
    status: 'success' | 'error' | 'warning' | 'info';
    message: string;
    data?: {
        [key:string] : any
    }
}