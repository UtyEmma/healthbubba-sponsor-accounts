export interface ApiResponse<TData extends object> {
    status: boolean;
    message: string;
    data: TData;
}
