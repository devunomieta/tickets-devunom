import {api} from "./client.ts";

export interface AdminPayoutRequest {
    id: number;
    organizer_id: number;
    organizer_name: string;
    organizer_email: string;
    amount: number;
    currency: string;
    status: 'PENDING' | 'PAID' | 'REJECTED';
    bank_name: string;
    account_number: string;
    account_name: string;
    transfer_reference: string | null;
    admin_note: string | null;
    paid_at: string | null;
    created_at: string;
}

export const payoutsClient = {
    getAdminPayouts: async (status?: string): Promise<AdminPayoutRequest[]> => {
        const response = await api.get<{ data: AdminPayoutRequest[] }>('/admin/payouts', {
            params: status ? {status} : undefined,
        });
        return response.data.data;
    },

    updateAdminPayout: async (
        payoutId: number,
        payload: { status: 'PAID' | 'REJECTED'; transfer_reference?: string; admin_note?: string }
    ) => {
        const response = await api.put(`/admin/payouts/${payoutId}`, payload);
        return response.data.data;
    },
};
