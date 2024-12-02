export interface StatusResponse {
    status: 'success' | 'error';
    message: string;
    data: any;
}
