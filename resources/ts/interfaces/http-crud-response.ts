export interface HttpCrudResponse {
    current_page?: number;
    data?: any[],
    last_page_url?: string;
    prev_page_url?: string;
    to?: number;
    from?: number;
    total?: number;
}