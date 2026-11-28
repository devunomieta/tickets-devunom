import {api} from "./client.ts";
import {publicApi} from "./public-client.ts";

export interface PlatformBrandingSettings {
    app_name: string;
    primary_color: string;
    logo_url: string | null;
    dark_logo_url: string | null;
    favicon_url: string | null;
}

export const applyBrandingToDom = (branding: PlatformBrandingSettings) => {
    if (typeof document === 'undefined') return;

    if (branding.primary_color) {
        document.documentElement.style.setProperty('--mantine-color-primary-filled', branding.primary_color);
        document.documentElement.style.setProperty('--mantine-color-primary-6', branding.primary_color);
        document.documentElement.style.setProperty('--mantine-color-primary-8', branding.primary_color);
        const themeMeta = document.querySelector('meta[name="theme-color"]');
        if (themeMeta) {
            themeMeta.setAttribute('content', branding.primary_color);
        }
    }

    if (branding.favicon_url) {
        const iconLinks = document.querySelectorAll<HTMLLinkElement>('link[rel*="icon"]');
        iconLinks.forEach((el) => {
            el.href = branding.favicon_url!;
        });
    }

    if (branding.app_name && document.title === 'Hi.Events') {
        document.title = branding.app_name;
    }
};

export const brandingClient = {
    getBranding: async (): Promise<PlatformBrandingSettings> => {
        const response = await publicApi.get<{ data: PlatformBrandingSettings }>('/branding');
        return response.data.data;
    },

    updateBranding: async (payload: PlatformBrandingSettings): Promise<PlatformBrandingSettings> => {
        const response = await api.put<{ data: PlatformBrandingSettings }>('/admin/branding', payload);
        return response.data.data;
    },
};
