import {api} from "./client.ts";
import {publicApi} from "./public-client.ts";

export type ThemeMode = 'dark' | 'light';

export interface PlatformBrandingSettings {
    app_name: string;
    primary_color: string;
    theme_mode: ThemeMode;
    accent_color: string;
    logo_url: string | null;
    dark_logo_url: string | null;
    favicon_url: string | null;
}

export const applyBrandingToDom = (branding: PlatformBrandingSettings) => {
    if (typeof document === 'undefined') return;

    document.documentElement.setAttribute('data-mantine-color-scheme', 'light');

    if (branding.primary_color) {
        document.documentElement.style.setProperty('--mantine-color-primary-filled', branding.primary_color);
        document.documentElement.style.setProperty('--mantine-color-primary-6', branding.primary_color);
        document.documentElement.style.setProperty('--mantine-color-primary-8', branding.primary_color);
        const themeMeta = document.querySelector('meta[name="theme-color"]');
        if (themeMeta) {
            themeMeta.setAttribute('content', branding.primary_color);
        }
    }

    if (branding.accent_color) {
        document.documentElement.style.setProperty('--mantine-color-secondary-filled', branding.accent_color);
        document.documentElement.style.setProperty('--mantine-color-secondary-5', branding.accent_color);
        document.documentElement.style.setProperty('--mantine-color-secondary-6', branding.accent_color);
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

    uploadAsset: async (type: 'logo' | 'dark_logo' | 'favicon', file: File) => {
        const formData = new FormData();
        formData.append('type', type);
        formData.append('file', file);
        const response = await api.post<{
            data: { type: string; url: string; disk: string; branding: PlatformBrandingSettings };
        }>('/admin/branding/upload', formData, {
            headers: {'Content-Type': 'multipart/form-data'},
        });
        return response.data.data;
    },
};
