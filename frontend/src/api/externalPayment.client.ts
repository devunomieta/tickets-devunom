import {api} from "./client.ts";
import {publicApi} from "./public-client.ts";
import {IdParam} from "../types.ts";

export type GatewayProvider = 'paystack' | 'bachs';
export type GatewayMode = 'test' | 'live';

export interface AccountPaymentGatewaySettings {
    provider: GatewayProvider;
    is_enabled: boolean;
    mode: GatewayMode;
    test_public_key: string;
    has_test_secret_key: boolean;
    has_test_webhook_secret: boolean;
    live_public_key: string;
    has_live_secret_key: boolean;
    has_live_webhook_secret: boolean;
    is_configured: boolean;
}

export interface UpdatePaymentGatewayPayload {
    is_enabled: boolean;
    mode: GatewayMode;
    test_public_key?: string;
    test_secret_key?: string;
    test_webhook_secret?: string;
    live_public_key?: string;
    live_secret_key?: string;
    live_webhook_secret?: string;
}

export interface EventExternalGateways {
    paystack: { enabled: boolean; mode: GatewayMode };
    bachs: { enabled: boolean; mode: GatewayMode };
}

export const externalPaymentClient = {
    getAccountGateways: async (accountId: IdParam) => {
        const response = await api.get<{ data: Record<GatewayProvider, AccountPaymentGatewaySettings> }>(
            `/accounts/${accountId}/payment-gateways`
        );
        return response.data.data;
    },

    updateAccountGateway: async (
        accountId: IdParam,
        provider: GatewayProvider,
        payload: UpdatePaymentGatewayPayload
    ) => {
        const response = await api.put<{ data: AccountPaymentGatewaySettings }>(
            `/accounts/${accountId}/payment-gateways/${provider}`,
            payload
        );
        return response.data.data;
    },

    getEventGateways: async (eventId: IdParam) => {
        const response = await publicApi.get<{ data: EventExternalGateways }>(
            `/events/${eventId}/payment-gateways`
        );
        return response.data.data;
    },

    createPaystackCheckout: async (eventId: IdParam, orderShortId: string) => {
        const response = await publicApi.post<{
            data: { authorization_url: string; access_code: string; reference: string };
        }>(`/events/${eventId}/order/${orderShortId}/paystack/checkout`);
        return response.data.data;
    },

    verifyPaystackPayment: async (eventId: IdParam, orderShortId: string, reference: string) => {
        const response = await publicApi.get<{ data: { status: string; reference: string } }>(
            `/events/${eventId}/order/${orderShortId}/paystack/verify`,
            {params: {reference}}
        );
        return response.data.data;
    },

    createBachsCheckout: async (eventId: IdParam, orderShortId: string) => {
        const response = await publicApi.post<{
            data: { checkout_url: string; payment_id: string; reference: string; status: string };
        }>(`/events/${eventId}/order/${orderShortId}/bachs/checkout`);
        return response.data.data;
    },
};
